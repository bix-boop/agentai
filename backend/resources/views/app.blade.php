<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phoenix AI</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
  <div class="max-w-5xl mx-auto p-6">
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Phoenix AI</h1>
      <a href="/installer" class="text-blue-600 hover:underline">Installer</a>
    </header>

    <section class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4">Quick Start</h2>
      <p class="text-gray-600 mb-4">Use the API endpoints directly or the installer to finish setup. This minimal page works without any frontend build.</p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="/api/v1/status" class="block p-4 border rounded hover:bg-gray-50">API Status</a>
        <a href="/health_check.php" class="block p-4 border rounded hover:bg-gray-50">Health Check</a>
        <a href="/debug.php" class="block p-4 border rounded hover:bg-gray-50">Debug Info</a>
      </div>
    </section>

    <section class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold mb-4">AI Assistants (Public)</h2>
      <div id="assistants" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
    </section>
  </div>

  <script>
    async function loadAssistants() {
      try {
        const res = await fetch('/api/v1/ai-assistants');
        const json = await res.json();
        const list = Array.isArray(json.data) ? json.data : [];
        const container = document.getElementById('assistants');
        container.innerHTML = list.map(a => `
          <div class="p-4 border rounded">
            <div class="flex items-center gap-3 mb-2">
              <img src="${a.avatar_url || ''}" class="w-10 h-10 rounded-full"/>
              <div>
                <div class="font-semibold">${a.name}</div>
                <div class="text-sm text-gray-500">${a.expertise || ''}</div>
              </div>
            </div>
            <p class="text-sm text-gray-700 mb-3">${(a.description || '').slice(0,140)}</p>
          </div>
        `).join('');
      } catch (e) {
        console.error(e);
      }
    }
    loadAssistants();
  </script>
</body>
</html>