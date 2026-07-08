<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\I18n;
use PDO;

final class FormResponseRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    public function create(int $versionId, int $dogId, ?int $ownerId, ?int $userId, ?string $note): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO form_responses (form_version_id, dog_id, owner_id, submitted_by_user_id, note)
             VALUES (:v, :d, :o, :u, :n)'
        );
        $stmt->execute(['v' => $versionId, 'd' => $dogId, 'o' => $ownerId, 'u' => $userId, 'n' => $note]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $v */
    public function addAnswer(int $responseId, int $questionId, array $v): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO form_answers (response_id, question_id, value_text, value_number, value_date, value_json, option_id)
             VALUES (:r, :q, :text, :num, :date, :json, :opt)'
        );
        $stmt->execute([
            'r' => $responseId,
            'q' => $questionId,
            'text' => $v['text'] ?? null,
            'num' => $v['number'] ?? null,
            'date' => $v['date'] ?? null,
            'json' => isset($v['json']) ? json_encode($v['json'], JSON_UNESCAPED_UNICODE) : null,
            'opt' => $v['option_id'] ?? null,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function responsesForDog(int $dogId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT r.id, r.submitted_at, r.note, fd.name AS form_name, v.version
             FROM form_responses r
             JOIN form_versions v ON v.id = r.form_version_id
             JOIN form_definitions fd ON fd.id = v.form_definition_id
             WHERE r.dog_id = :d
             ORDER BY r.submitted_at DESC'
        );
        $stmt->execute(['d' => $dogId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> vyplnene dotazniky pro aktualni psy majitele */
    public function responsesForOwner(int $ownerId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT r.id, r.submitted_at, fd.name AS form_name, v.version, dg.name AS dog_name
             FROM form_responses r
             JOIN form_versions v ON v.id = r.form_version_id
             JOIN form_definitions fd ON fd.id = v.form_definition_id
             JOIN dogs dg ON dg.id = r.dog_id
             JOIN dog_owners do2 ON do2.dog_id = r.dog_id AND do2.is_current = 1 AND do2.owner_id = :o
             ORDER BY r.submitted_at DESC'
        );
        $stmt->execute(['o' => $ownerId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT r.*, v.version, fd.name AS form_name, dg.name AS dog_name
             FROM form_responses r
             JOIN form_versions v ON v.id = r.form_version_id
             JOIN form_definitions fd ON fd.id = v.form_definition_id
             JOIN dogs dg ON dg.id = r.dog_id
             WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function answers(int $responseId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT a.*, q.label, q.type, q.question_key, q.position
             FROM form_answers a
             JOIN form_questions q ON q.id = a.question_id
             WHERE a.response_id = :r
             ORDER BY q.position ASC, q.id ASC'
        );
        $stmt->execute(['r' => $responseId]);
        return $stmt->fetchAll();
    }

    /**
     * Odpovedi prelozene do $locale: text otazky (label) i hodnoty voleb se
     * re-renderuji z reference (option_id / klice), ne z ceskeho snapshotu
     * value_text. Volny text/cislo/datum/soubor zustavaji tak, jak byly zadany.
     * Kazdy radek dostane 'display_value' (prelozena hodnota k zobrazeni).
     *
     * @return array<int, array<string, mixed>>
     */
    public function answersLocalized(int $responseId, string $locale): array
    {
        $answers = $this->answers($responseId);
        if ($answers === []) {
            return [];
        }

        $tx = new TranslationRepository();
        // Text otazek prelozit (klic = question_id).
        $tx->apply(TranslationRepository::FORM_QUESTION, $answers, ['label'], $locale, 'question_id');

        // Moznosti otazek z odpovedi + jejich preklady (pro re-render voleb).
        $qids = array_values(array_unique(array_map(static fn ($a): int => (int) $a['question_id'], $answers)));
        $optRows = $this->optionsForQuestions($qids);
        $tx->apply(TranslationRepository::FORM_OPTION, $optRows, ['label'], $locale);
        $byOptId = [];
        $byQKey = [];
        foreach ($optRows as $o) {
            $byOptId[(int) $o['id']] = (string) $o['label'];
            $byQKey[(int) $o['question_id']][(string) $o['option_key']] = (string) $o['label'];
        }

        foreach ($answers as &$a) {
            $type = (string) $a['type'];
            $json = !empty($a['value_json']) ? (json_decode((string) $a['value_json'], true) ?: []) : [];
            $display = (string) ($a['value_text'] ?? '');

            if ($type === 'single_choice' && !empty($a['option_id']) && isset($byOptId[(int) $a['option_id']])) {
                $display = $byOptId[(int) $a['option_id']];
            } elseif ($type === 'multiple_choice' && is_array($json) && $json !== []) {
                $qid = (int) $a['question_id'];
                $labels = [];
                foreach ($json as $key) {
                    $labels[] = $byQKey[$qid][(string) $key] ?? (string) $key;
                }
                $display = implode(', ', $labels);
            } elseif ($type === 'yes_no') {
                $v = strtolower(trim((string) ($a['value_text'] ?? '')));
                if ($v === 'ano' || $v === 'yes') {
                    $display = I18n::t('Ano');
                } elseif ($v === 'ne' || $v === 'no') {
                    $display = I18n::t('Ne');
                }
            } elseif ($type === 'disease_history' && is_array($json) && $json !== []) {
                $lines = [];
                foreach ($json as $e) {
                    if (!is_array($e)) {
                        continue;
                    }
                    $label = I18n::td('death_causes', (string) ($e['code'] ?? ''), (string) ($e['label'] ?? ''));
                    $from = \App\Support\Dates::toCz((string) ($e['from'] ?? ''));
                    $end = !empty($e['ongoing'])
                        ? I18n::t('stále probíhá')
                        : (!empty($e['to']) ? \App\Support\Dates::toCz((string) $e['to']) : '?');
                    $line = $label . ' (' . $from . ' – ' . $end . ')';
                    if (!empty($e['note'])) {
                        $line .= ' – ' . (string) $e['note'];
                    }
                    $lines[] = $line;
                }
                if ($lines !== []) {
                    $display = implode("\n", $lines);
                }
            }
            $a['display_value'] = $display;
        }
        unset($a);

        return $answers;
    }

    /**
     * Moznosti pro dane otazky (bez N+1) - pro re-render prelozenych voleb.
     *
     * @param array<int, int> $questionIds
     * @return array<int, array<string, mixed>>
     */
    private function optionsForQuestions(array $questionIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $questionIds)));
        if ($ids === []) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo()->prepare(
            "SELECT id, question_id, option_key, label FROM form_question_options
             WHERE question_id IN ({$ph}) ORDER BY question_id ASC, position ASC, id ASC"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }
}
