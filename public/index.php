<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";

start_session();

$u = current_user();
if (!$u) {
    header("Location: /sistema/public/login.php");
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="color-scheme" content="light dark" />
  <meta name="theme-color" content="#0b1220" />
  <title>Sistema</title>

  <link rel="stylesheet" href="/sistema/public/style.css" />

  <!-- Responsividade global mínima (mantida aqui por compatibilidade com seu layout atual) -->
  <style>
    html, body { overflow-x: hidden; }
    * { box-sizing: border-box; }

    /* Alvos de toque melhores no mobile */
    button, .btn, a.btn { min-height: 44px; }

    /* Overlay: escondido por padrão */
    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      backdrop-filter: blur(2px);
      z-index: 40;
    }

    /* Sidebar/topbar z-index para drawer */
    .sidebar { z-index: 50; }
    .topbar  { z-index: 30; position: sticky; top: 0; }

    /* Classe aplicada no <body> quando o menu abre no mobile */
    body.is-nav-open { overflow: hidden; }
    body.is-nav-open .overlay { display: block; }

    /* ====== Mobile/Tablet: sidebar vira drawer ====== */
    @media (max-width: 980px){
      .app { min-height: 100dvh; }

      .sidebar{
        position: fixed;
        top: 0;
        left: 0;
        height: 100dvh;
        transform: translateX(-110%);
        transition: transform .18s ease;
        will-change: transform;
      }

      body.is-nav-open .sidebar{
        transform: translateX(0);
      }

      .main{
        width: 100%;
        margin-left: 0 !important;
      }

      .content{
        padding-left: 12px;
        padding-right: 12px;
      }
    }
  </style>

  <!-- (Opcional) Evita bloquear a renderização -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin />
  <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
</head>

<body>
  <div class="app">
    <aside class="sidebar" id="sidebar" aria-label="Menu lateral">
      <div class="sidebar__brand">
        <div class="brand__logo">SE</div>
        <div class="brand__text">
          <div class="brand__title">Sistema</div>
          <div class="brand__subtitle">Empresa</div>
        </div>
      </div>

      <nav class="nav" id="nav"></nav>

      <div class="sidebar__footer">
        <div class="user">
          <div class="user__avatar" id="userAvatar">A</div>
          <div class="user__meta">
            <div class="user__name" id="userName">
              <?= htmlspecialchars($u["name"] ?? "Usuário") ?>
            </div>
            <div class="user__role" id="userRole">
              <?= htmlspecialchars($u["role"] ?? "") ?>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- overlay para fechar o drawer no mobile -->
    <div class="overlay" id="overlay" aria-hidden="true"></div>

    <main class="main" id="main">
      <header class="topbar" role="banner">
        <div class="topbar__left">
          <button
            type="button"
            id="btnToggleSidebar"
            class="icon-btn"
            aria-label="Abrir menu"
            aria-controls="sidebar"
            aria-expanded="false"
            title="Menu"
          >☰</button>
        </div>

        <!-- Mostra só a página atual (title); path fica escondido -->
        <div class="breadcrumbs">
          <div class="breadcrumbs__title" id="topbarTitle">Sistema</div>
          <div class="breadcrumbs__path" id="topbarPath" style="display:none;"></div>
        </div>

        <div class="topbar__actions">
          <button type="button" id="btnLogout" class="btn" title="Sair">Sair</button>
        </div>
      </header>

      <!-- onde o app.js injeta as páginas -->
      <section class="content" id="view">
        <h1>Bem-vindo, <?= htmlspecialchars($u["name"] ?? "") ?></h1>
      </section>
    </main>
  </div>

  <script>
    window.__USER__ = <?= json_encode($u ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    // Drawer fallback (caso o app.js não faça ou faça parcialmente)
    (function () {
      const btn = document.getElementById("btnToggleSidebar");
      const overlay = document.getElementById("overlay");

      function setOpen(open) {
        document.body.classList.toggle("is-nav-open", open);
        if (btn) btn.setAttribute("aria-expanded", open ? "true" : "false");
        if (overlay) overlay.setAttribute("aria-hidden", open ? "false" : "true");
      }

      btn?.addEventListener("click", () => {
        const open = document.body.classList.contains("is-nav-open");
        setOpen(!open);
      });

      overlay?.addEventListener("click", () => setOpen(false));

      window.addEventListener("hashchange", () => setOpen(false));

      window.addEventListener("keydown", (e) => {
        if (e.key === "Escape") setOpen(false);
      });
    })();

    // Fallback: mostra só o nome da página baseado no hash (#/rh-desligamento -> "RH / Desligamento")
    (function () {
      const titleEl = document.getElementById("topbarTitle");
      const pathEl  = document.getElementById("topbarPath");

      // Ajuste/adicione rotas aqui se quiser nomes 100% personalizados
      const map = {
        "rh-desligamento": "RH / Desligamento",
        "balanca-previa": "Balança / Prévia",
        "balanca-resumo": "Balança / Resumo",
        "": "Dashboard"
      };

      function prettyFromHash() {
        const raw = (location.hash || "#/").replace(/^#\//, "").split("?")[0];
        if (map[raw]) return map[raw];

        // fallback genérico: "estoque-entrada" -> "ESTOQUE / Entrada"
        const parts = raw.split("-").filter(Boolean);
        if (!parts.length) return "Dashboard";
        const first = parts.shift();
        const a = first.toUpperCase();
        const b = parts.map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(" ");
        return b ? `${a} / ${b}` : a;
      }

      function render() {
        if (titleEl) titleEl.textContent = prettyFromHash();
        if (pathEl) {
          pathEl.textContent = "";
          pathEl.style.display = "none";
        }
      }

      window.addEventListener("hashchange", render);
      render();
    })();
  </script>
<?php
// === DEBUG TEMPORÁRIO - REMOVA DEPOIS ===
$u = current_user();
if ($u) {
    echo "<script>console.log('Usuário da sessão:', " . json_encode($u) . ");</script>";
    echo "<script>console.log('Role atual:', " . json_encode($u['role'] ?? null) . ");</script>";
}
?>
  <script src="/sistema/public/app.js?v=1" defer></script>
</body>
</html>