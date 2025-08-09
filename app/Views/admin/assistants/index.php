<?php use App\Core\Session; ?>
<h2>Assistants</h2>
<p><a class="btn" href="/admin/assistants/create">New Assistant</a></p>
<table class="table">
  <tr>
    <th>ID</th><th>Name</th><th>Slug</th><th>Visibility</th><th>Actions</th>
  </tr>
  <?php foreach (($assistants ?? []) as $a): ?>
    <tr>
      <td><?= (int)$a['id'] ?></td>
      <td><?= htmlspecialchars($a['name']) ?></td>
      <td><?= htmlspecialchars($a['slug']) ?></td>
      <td><?= htmlspecialchars($a['visibility']) ?></td>
      <td>
        <a href="/admin/assistants/<?= (int)$a['id'] ?>/edit">Edit</a>
        <form action="/admin/assistants/<?= (int)$a['id'] ?>/delete" method="post" style="display:inline" onsubmit="return confirm('Delete this assistant?');">
          <button class="btn-link" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>