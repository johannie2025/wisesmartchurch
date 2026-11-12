<?php
require_once __DIR__ . '/../lib.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0);
    switch ($_POST['a'] ?? '') {
        case 'ok': q('UPDATE testimonials SET approved=1 WHERE id=?', [$id]); break;
        case 'hide': q('UPDATE testimonials SET approved=0 WHERE id=?', [$id]); break;
        case 'del': q('DELETE FROM testimonials WHERE id=?', [$id]); break;
        case 'add': if (trim($_POST['name'] ?? '') && trim($_POST['body'] ?? '')) q('INSERT INTO testimonials (name,role,church,body,approved) VALUES (?,?,?,?,1)', [trim($_POST['name']), trim($_POST['role']), trim($_POST['church']), trim($_POST['body'])]); break;
    }
}
page_head('Témoignages');
echo '<h1>Témoignages</h1><p style="color:var(--mut)">Seuls les témoignages approuvés s\'affichent sur le site. N\'ajoutez que de vrais avis, avec l\'accord de leur auteur.</p><table><tr><th>Date</th><th>Auteur</th><th>Texte</th><th>État</th><th></th></tr>';
foreach (all('SELECT * FROM testimonials ORDER BY approved, id DESC') as $t) {
    $b = fn($a, $l, $c = 'sec') => '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="a" value="' . $a . '"><input type="hidden" name="id" value="' . (int)$t['id'] . '"><button class="' . $c . '">' . $l . '</button></form> ';
    echo '<tr><td>' . h($t['created_at']) . '</td><td>' . h($t['name']) . '<br><small>' . h($t['role'] . ' ' . $t['church']) . '</small></td><td>' . nl2br(h($t['body'])) . '</td><td>' . ($t['approved'] ? '<span class="badge active">publié</span>' : '<span class="badge exp">en attente</span>') . '</td><td>' . ($t['approved'] ? $b('hide', 'Masquer') : $b('ok', 'Approuver', '')) . $b('del', 'Suppr.', 'bad') . '</td></tr>';
}
echo '</table><h2>Ajouter un témoignage reçu</h2><form method="post">' . csrf_field() . '<input type="hidden" name="a" value="add"><div class="row"><input name="name" placeholder="Nom" required><input name="role" placeholder="Rôle"><input name="church" placeholder="Église"></div><textarea name="body" rows="3" cols="60" placeholder="Texte" required></textarea><p><button>Publier</button></p></form>';
page_foot();
