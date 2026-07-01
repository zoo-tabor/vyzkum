<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Číselník zemí (ISO 3166-1 alpha-3) s českými názvy pro výběr země původu psa.
 * Sjednocené třípísmenné kódy; seznam lze doplnit dle potřeby.
 */
final class Countries
{
    /** @var array<string, string> kód => český název */
    public const LIST = [
        'AFG' => 'Afghánistán', 'ALB' => 'Albánie', 'DZA' => 'Alžírsko', 'AND' => 'Andorra',
        'AGO' => 'Angola', 'ARG' => 'Argentina', 'ARM' => 'Arménie', 'AUS' => 'Austrálie',
        'AUT' => 'Rakousko', 'AZE' => 'Ázerbájdžán', 'BHS' => 'Bahamy', 'BHR' => 'Bahrajn',
        'BGD' => 'Bangladéš', 'BRB' => 'Barbados', 'BLR' => 'Bělorusko', 'BEL' => 'Belgie',
        'BLZ' => 'Belize', 'BEN' => 'Benin', 'BTN' => 'Bhútán', 'BOL' => 'Bolívie',
        'BIH' => 'Bosna a Hercegovina', 'BWA' => 'Botswana', 'BRA' => 'Brazílie', 'BRN' => 'Brunej',
        'BGR' => 'Bulharsko', 'BFA' => 'Burkina Faso', 'BDI' => 'Burundi', 'KHM' => 'Kambodža',
        'CMR' => 'Kamerun', 'CAN' => 'Kanada', 'CPV' => 'Kapverdy', 'CAF' => 'Středoafrická republika',
        'TCD' => 'Čad', 'CHL' => 'Chile', 'CHN' => 'Čína', 'COL' => 'Kolumbie',
        'COM' => 'Komory', 'COG' => 'Kongo', 'COD' => 'Kongo (DR)', 'CRI' => 'Kostarika',
        'CIV' => 'Pobřeží slonoviny', 'HRV' => 'Chorvatsko', 'CUB' => 'Kuba', 'CYP' => 'Kypr',
        'CZE' => 'Česko', 'DNK' => 'Dánsko', 'DJI' => 'Džibutsko', 'DMA' => 'Dominika',
        'DOM' => 'Dominikánská republika', 'ECU' => 'Ekvádor', 'EGY' => 'Egypt', 'SLV' => 'Salvador',
        'GNQ' => 'Rovníková Guinea', 'ERI' => 'Eritrea', 'EST' => 'Estonsko', 'SWZ' => 'Eswatini',
        'ETH' => 'Etiopie', 'FJI' => 'Fidži', 'FIN' => 'Finsko', 'FRA' => 'Francie',
        'GAB' => 'Gabon', 'GMB' => 'Gambie', 'GEO' => 'Gruzie', 'DEU' => 'Německo',
        'GHA' => 'Ghana', 'GRC' => 'Řecko', 'GRD' => 'Grenada', 'GTM' => 'Guatemala',
        'GIN' => 'Guinea', 'GNB' => 'Guinea-Bissau', 'GUY' => 'Guyana', 'HTI' => 'Haiti',
        'HND' => 'Honduras', 'HUN' => 'Maďarsko', 'ISL' => 'Island', 'IND' => 'Indie',
        'IDN' => 'Indonésie', 'IRN' => 'Írán', 'IRQ' => 'Irák', 'IRL' => 'Irsko',
        'ISR' => 'Izrael', 'ITA' => 'Itálie', 'JAM' => 'Jamajka', 'JPN' => 'Japonsko',
        'JOR' => 'Jordánsko', 'KAZ' => 'Kazachstán', 'KEN' => 'Keňa', 'KIR' => 'Kiribati',
        'PRK' => 'Severní Korea', 'KOR' => 'Jižní Korea', 'KWT' => 'Kuvajt', 'KGZ' => 'Kyrgyzstán',
        'LAO' => 'Laos', 'LVA' => 'Lotyšsko', 'LBN' => 'Libanon', 'LSO' => 'Lesotho',
        'LBR' => 'Libérie', 'LBY' => 'Libye', 'LIE' => 'Lichtenštejnsko', 'LTU' => 'Litva',
        'LUX' => 'Lucembursko', 'MDG' => 'Madagaskar', 'MWI' => 'Malawi', 'MYS' => 'Malajsie',
        'MDV' => 'Maledivy', 'MLI' => 'Mali', 'MLT' => 'Malta', 'MHL' => 'Marshallovy ostrovy',
        'MRT' => 'Mauritánie', 'MUS' => 'Mauricius', 'MEX' => 'Mexiko', 'FSM' => 'Mikronésie',
        'MDA' => 'Moldavsko', 'MCO' => 'Monako', 'MNG' => 'Mongolsko', 'MNE' => 'Černá Hora',
        'MAR' => 'Maroko', 'MOZ' => 'Mosambik', 'MMR' => 'Myanmar', 'NAM' => 'Namibie',
        'NRU' => 'Nauru', 'NPL' => 'Nepál', 'NLD' => 'Nizozemsko', 'NZL' => 'Nový Zéland',
        'NIC' => 'Nikaragua', 'NER' => 'Niger', 'NGA' => 'Nigérie', 'MKD' => 'Severní Makedonie',
        'NOR' => 'Norsko', 'OMN' => 'Omán', 'PAK' => 'Pákistán', 'PLW' => 'Palau',
        'PAN' => 'Panama', 'PNG' => 'Papua-Nová Guinea', 'PRY' => 'Paraguay', 'PER' => 'Peru',
        'PHL' => 'Filipíny', 'POL' => 'Polsko', 'PRT' => 'Portugalsko', 'QAT' => 'Katar',
        'ROU' => 'Rumunsko', 'RUS' => 'Rusko', 'RWA' => 'Rwanda', 'KNA' => 'Svatý Kryštof a Nevis',
        'LCA' => 'Svatá Lucie', 'VCT' => 'Svatý Vincenc a Grenadiny', 'WSM' => 'Samoa', 'SMR' => 'San Marino',
        'STP' => 'Svatý Tomáš a Princův ostrov', 'SAU' => 'Saúdská Arábie', 'SEN' => 'Senegal',
        'SRB' => 'Srbsko', 'SYC' => 'Seychely', 'SLE' => 'Sierra Leone', 'SGP' => 'Singapur',
        'SVK' => 'Slovensko', 'SVN' => 'Slovinsko', 'SLB' => 'Šalomounovy ostrovy', 'SOM' => 'Somálsko',
        'ZAF' => 'Jihoafrická republika', 'SSD' => 'Jižní Súdán', 'ESP' => 'Španělsko', 'LKA' => 'Srí Lanka',
        'SDN' => 'Súdán', 'SUR' => 'Surinam', 'SWE' => 'Švédsko', 'CHE' => 'Švýcarsko',
        'SYR' => 'Sýrie', 'TWN' => 'Tchaj-wan', 'TJK' => 'Tádžikistán', 'TZA' => 'Tanzanie',
        'THA' => 'Thajsko', 'TLS' => 'Východní Timor', 'TGO' => 'Togo', 'TON' => 'Tonga',
        'TTO' => 'Trinidad a Tobago', 'TUN' => 'Tunisko', 'TUR' => 'Turecko', 'TKM' => 'Turkmenistán',
        'TUV' => 'Tuvalu', 'UGA' => 'Uganda', 'UKR' => 'Ukrajina', 'ARE' => 'Spojené arabské emiráty',
        'GBR' => 'Spojené království', 'USA' => 'Spojené státy', 'URY' => 'Uruguay', 'UZB' => 'Uzbekistán',
        'VUT' => 'Vanuatu', 'VAT' => 'Vatikán', 'VEN' => 'Venezuela', 'VNM' => 'Vietnam',
        'YEM' => 'Jemen', 'ZMB' => 'Zambie', 'ZWE' => 'Zimbabwe',
    ];

    /** @return array<string, string> kód => název, seřazeno podle názvu */
    public static function all(): array
    {
        $list = self::LIST;
        asort($list, SORT_LOCALE_STRING);
        return $list;
    }

    public static function name(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }
        return self::LIST[strtoupper($code)] ?? $code;
    }

    public static function isValid(string $code): bool
    {
        return isset(self::LIST[strtoupper($code)]);
    }
}
