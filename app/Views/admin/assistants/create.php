<?php use App\Core\Session; ?>
<h2>Create Assistant</h2>
<form action="/admin/assistants" method="post" enctype="multipart/form-data" class="form">
  <div class="form-group"><label>Name</label><input name="name" required></div>
  <div class="form-group"><label>Slug</label><input name="slug" required></div>
  <div class="form-group"><label>Expertise</label><input name="expertise"></div>
  <div class="form-group"><label>Description</label><input name="description"></div>
  <div class="form-group"><label>Training</label><textarea name="training" rows="6"></textarea></div>
  <div class="form-group"><label>Config (JSON)</label><textarea name="config_json" rows="4">{"temperature":0.7,"presence_penalty":0,"frequency_penalty":0}</textarea></div>
  <div class="form-group"><label>Visibility</label>
    <select name="visibility">
      <option value="public">Public</option>
      <option value="private">Private</option>
    </select>
  </div>
  <div class="form-group"><label>Avatar</label><input type="file" name="avatar" accept="image/*"></div>
  <button class="btn" type="submit">Create</button>
</form>