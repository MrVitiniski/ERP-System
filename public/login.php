<?php
declare(strict_types=1);
require_once __DIR__ . "/includes/auth.php";

// Se já estiver logado, redireciona
start_session();
if (current_user()) {
    header("Location: /sistema/public/index.php");
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
  <link rel="stylesheet" href="/sistema/public/style.css" />
  <style>
    body { font-family: system-ui, Arial; background:#0b1220; color:#e8eefc; }
    .wrap { max-width: 420px; margin: 10vh auto; background:#111a2e; padding:24px; border-radius:12px; }
    label { display:block; margin:12px 0 6px; }
    input { width:100%; padding:10px 12px; border-radius:10px; border:1px solid #223052; background:#0b1220; color:#e8eefc; }
    button { width:100%; margin-top:16px; padding:10px 12px; border-radius:10px; border:0; background:#3b82f6; color:white; font-weight:600; cursor:pointer; }
    .err { margin-top:12px; color:#ffb4b4; }
    .hint { margin-top:12px; opacity:.8; font-size: 12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Entrar</h1>
    <form id="formLogin">
      <label for="username">Usuário</label>
      <input id="username" name="username" autocomplete="username" required />
      <label for="password">Senha</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required />
      <button type="submit" id="btn">Entrar</button>
      <div class="err" id="err" hidden></div>
      <div class="hint">URL do sistema: <code>/sistema/public/</code></div>
    </form>
  </div>

  <script>
    const BASE = "/sistema/public";
    const form = document.getElementById("formLogin");
    const err  = document.getElementById("err");
    const btn  = document.getElementById("btn");

    form.addEventListener("submit", async (ev) => {
      ev.preventDefault();
      err.hidden = true;
      btn.disabled = true;
      btn.textContent = "Carregando...";

      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value;

      try {
        const res = await fetch(`${BASE}/api/login.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify({ username, password }),
        });

        const data = await res.json();

        if (data.ok) {
          window.location.replace("index.php");
        } else {
          err.textContent = data.error || "Usuário ou senha incorretos";
          err.hidden = false;
        }
      } catch (e) {
        console.error("Erro de rede:", e);
        err.textContent = "Erro de comunicação com o servidor.";
        err.hidden = false;
      } finally {
        btn.disabled = false;
        btn.textContent = "Entrar";
      }
    });
  </script>
</body>
</html>