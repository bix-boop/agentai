<h2>Assistants</h2>
<div class="grid">
  <?php foreach (($assistants ?? []) as $a): ?>
    <div class="card">
      <div class="card-body">
        <h3><a href="/a/<?= htmlspecialchars($a['slug']) ?>"><?= htmlspecialchars($a['name']) ?></a></h3>
        <p><?= htmlspecialchars($a['expertise'] ?? '') ?></p>
        <p class="muted"><?= htmlspecialchars($a['description'] ?? '') ?></p>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<style>
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem}
.card{border:1px solid #23262b;border-radius:.5rem;background:#0f1115}
.card-body{padding:1rem}
.muted{color:#aeb4bd}
</style>