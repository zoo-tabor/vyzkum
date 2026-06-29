<?php
declare(strict_types=1);

use App\Support\Policy;

$admin = ['id' => 1, 'role' => 'research_admin'];
$club = ['id' => 2, 'role' => 'club_viewer'];

test('hasRole accepts matching role', function () use ($admin) {
    assert_true(Policy::hasRole($admin, 'research_admin'));
    assert_true(Policy::hasRole($admin, ['vet', 'research_admin']));
});

test('hasRole rejects wrong role and null user', function () use ($club) {
    assert_false(Policy::hasRole($club, 'research_admin'));
    assert_false(Policy::hasRole(null, 'owner'));
});

test('research_admin can see all breeds', function () use ($admin, $club) {
    assert_true(Policy::canSeeAllBreeds($admin));
    assert_false(Policy::canSeeAllBreeds($club));
});

test('breed access is scoped for non-admins', function () use ($admin, $club) {
    assert_true(Policy::canAccessBreed($admin, 999, []), 'admin vidi vse');
    assert_true(Policy::canAccessBreed($club, 5, [5, 7]), 'klub vidi povolene');
    assert_false(Policy::canAccessBreed($club, 8, [5, 7]), 'klub nevidi cizi plemeno');
    assert_false(Policy::canAccessBreed($club, null, [5, 7]), 'null plemeno nepovoleno');
});
