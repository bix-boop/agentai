<?php /** @var array $assistant */ ?>
<h2>Edit Assistant</h2>
<form action="/admin/assistants/<?= (int)$assistant['id'] ?>" method="post" enctype="multipart/form-data" class="form">
  <div class="form-group"><label>Name</label><input name="name" value="<?= htmlspecialchars($assistant['name']) ?>" required></div>
  <div class="form-group"><label>Slug</label><input name="slug" value="<?= htmlspecialchars($assistant['slug']) ?>" required></div>
  <div class="form-group"><label>Expertise</label><input name="expertise" value="<?= htmlspecialchars($assistant['expertise'] ?? '') ?>"></div>
  <div class="form-group"><label>Description</label><input name="description" value="<?= htmlspecialchars($assistant['description'] ?? '') ?>"></div>
  <div class="form-group"><label>Training</label><textarea name="training" rows="6"><?= htmlspecialchars($assistant['training'] ?? '') ?></textarea></div>
  <div class="form-group"><label>Config (JSON)</label><textarea name="config_json" rows="4"><?= htmlspecialchars($assistant['config_json'] ?? '{}') ?></textarea></div>
  <div class="form-group"><label>Visibility</label>
    <select name="visibility">
      <option value="public" <?= ($assistant['visibility']==='public'?'selected':'') ?>>Public</option>
      <option value="private" <?= ($assistant['visibility']==='private'?'selected':'') ?>>Private</option>
    </select>
  </div>
  <?php if (!empty($assistant['avatar_path'])): ?>
    <p>Current avatar:<br>
      <img src="<?= htmlspecialchars($assistant['avatar_path']) ?>" alt="avatar" style="max-height:100px;border-radius:6px;border:1px solid #23262b"></p>
  <?php endif; ?>
  <div class="form-group"><label>Avatar</label><input type="file" name="avatar" accept="image/*"></div>
  <button class="btn" type="submit">Save</button>
</form>