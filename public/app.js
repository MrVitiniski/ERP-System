// Base global do app
window.APP_BASE = window.APP_BASE || "/sistema/public";
const BASE = window.APP_BASE;

const view = document.getElementById("view");
const nav = document.getElementById("nav");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const topbarTitle = document.getElementById("topbarTitle");
const topbarPath = document.getElementById("topbarPath");

const FORBIDDEN_PAGE = `${BASE}/forbidden.html`;

// ===== User / permissions normalization =====
function normRole(s) {
  return String(s || "")
    .trim()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, ""); 
}

function getUserFromWindow() {
  const u = window.__USER__ || {};
  
  // 1. Tenta pegar roles de array ou string
  let rolesRaw = [];
  if (Array.isArray(u.roles)) {
    rolesRaw = u.roles;
  } else if (typeof u.role === "string" && u.role.trim()) {
    rolesRaw = u.role.includes(",") ? u.role.split(",") : [u.role];
  }

  // 2. Normaliza e remove vazios
  const roles = rolesRaw.map(normRole).filter(Boolean);
  const name = u.name || "Usuário";
  
  // 3. Define role principal para o badge
  const role = roles.includes("admin") ? "admin" : (roles[0] || "");

  return { ...u, name, role, roles };
}

// Inicializa a constante user CORRETAMENTE
const user = getUserFromWindow();


// ===== UI: sidebar =====
const btnToggleSidebar = document.getElementById("btnToggleSidebar");
if (btnToggleSidebar) {
  btnToggleSidebar.addEventListener("click", () => {
    const isMobile = window.matchMedia("(max-width: 980px)").matches;
    if (isMobile) {
      sidebar.classList.toggle("is-open");
      const open = sidebar.classList.contains("is-open");
      overlay.classList.toggle("is-visible", open);
      overlay.setAttribute("aria-hidden", open ? "false" : "true");
    } else {
      sidebar.classList.toggle("is-collapsed");
    }
  });
}

if (overlay) {
  overlay.addEventListener("click", () => {
    sidebar.classList.remove("is-open");
    overlay.classList.remove("is-visible");
    overlay.setAttribute("aria-hidden", "true");
  });
}

// ===== auth: logout =====
const btnLogout = document.getElementById("btnLogout");
if (btnLogout) {
  btnLogout.addEventListener("click", (e) => {
    e.preventDefault();
    window.location.href = `${BASE}/logout.php`;
  });
}

// ===== MENU =====
const MENU = [
  {
    group: "Principal",
    items: [{ id: "dashboard", label: "Dashboard", icon: "⌂", page: `${BASE}/pages/dashboard.html` }],
  },
  {
    group: "Módulos",
    items: [
      {
        id: "rh",
        label: "RH",
        icon: "👥",
        roles: ["admin", "rh"],
        children: [
          { id: "rh-cadastrar", label: "Cadastrar Funcionário", page: `${BASE}/pages/rh/cadastrar-funcionario.html` },
          { id: "rh-listar", label: "Listar Funcionários", page: `${BASE}/pages/rh/listar-funcionarios.html` },
          { id: "rh-ferias", label: "Férias", page: `${BASE}/pages/rh/ferias.html` },
          { id: "rh-desligamento", label: "Desligamento", page: `${BASE}/pages/rh/desligamento.html` },
        ],
      },

      {
        id: "financeiro",
        label: "Financeiro",
        icon: "💵",
        roles: ["admin", "financeiro"],
        children: [
          { id: "fin-pagar", label: "Contas a Pagar", page: `${BASE}/pages/financeiro/contas-a-pagar.html` },
          { id: "fin-vencimentos", label: "Vencimentos", page: `${BASE}/pages/financeiro/vencimentos.html` },
          { id: "fin-caixa-dia", label: "Caixa do Dia", page: `${BASE}/pages/financeiro/caixa-do-dia.html` },
          { id: "fin-relatorio", label: "Relatório (Auditoria)", page: `${BASE}/pages/financeiro/relatorio.html` },
          { id: "fin-relatorio-dia", label: "Relatório Diário", page: `${BASE}/pages/financeiro/relatorio-diario.html` },
        ],
      },

      {
        id: "compras",
        label: "Compras",
        icon: "🧾",
        roles: ["admin", "compras"],
        children: [
          { id: "compras-solicitacao", label: "Solicitação de Compra", page: `${BASE}/pages/compras/solicitacao.html` },
          { id: "compras-fornecedor", label: "Cadastro de Fornecedor", page: `${BASE}/pages/compras/fornecedor.html` },
          { id: "compras-baixo-estoque", label: "Relatório baixo estoque", page: `${BASE}/pages/compras/baixo-estoque.html` },
          { id: "compras-lista-solicitacoes", label: "Lista de Solicitações", page: `${BASE}/pages/compras/solicitacoes.html` },
        ],
      },

      {
        id: "almoxarifado",
        label: "Almoxarifado",
        icon: "📦",
        roles: ["admin", "almoxarifado"],
        children: [
          { id: "alm-entrada", label: "Entrada", page: `${BASE}/pages/almoxarifado/entrada.html` },
          { id: "alm-saida", label: "Saída", page: `${BASE}/pages/almoxarifado/saida.html` },
          { id: "alm-estoque", label: "Estoque", page: `${BASE}/pages/almoxarifado/estoque.html` },
          { id: "alm-relatorios", label: "Relatórios", page: `${BASE}/pages/almoxarifado/relatorios.html` },
          { id: "alm-pedidos-epi", label: "Pedidos de EPI", page: `${BASE}/pages/almoxarifado/pedidos-epi.html` },
        ],
      },

      {
        id: "mecanica",
        label: "Mecânica",
        icon: "🛠",
        roles: ["admin", "mecanica"],
        children: [
          { id: "mec-os", label: "Nova OS / Encerrar OS", page: `${BASE}/pages/mecanica/os.html` },
          { id: "mec-relatorio", label: "Relatório", page: `${BASE}/pages/mecanica/relatorio.html` },
        ],
      },

      {
        id: "oficina",
        label: "Oficina",
        icon: "🏭",
        roles: ["admin", "oficina"],
        children: [
          { id: "ofi-os", label: "Nova OS / Encerrar OS", page: `${BASE}/pages/oficina/os.html` },
          { id: "ofi-frota-add", label: "Add Frota", page: `${BASE}/pages/oficina/frota-add.html` },
          { id: "ofi-frota-rel", label: "Relatório de Frota", page: `${BASE}/pages/oficina/frota-relatorio.html` },
        ],
      },

            {
        id: "abastecimento",
        label: "Abastecimento",
        icon: "⛽",
        roles: ["admin", "oficina"], // ajuste se quiser outro papel (ex: "abastecimento")
        children: [
          {
        id: "recebimento-diesel",
        label: "Recebimento Diesel",
        page: `${BASE}/pages/abastecimento/recebimento-diesel.html`,
          },
          {
            id: "abastecimento-frota",
            label: "Frota",
            page: `${BASE}/pages/abastecimento/abastecimento-frota.html`,
          },
          {
        id: "abastecimento-frota-relatorio",
        label: "Relatório",
        page: `${BASE}/pages/abastecimento/abastecimento-frota-relatorio.html`,
          },
        ],
      },

      // IMPORTANTE: Laboratório agora também exige permissão (antes estava liberado pra todo mundo)
      {
        id: "laboratorio",
        label: "Laboratório",
        icon: "🧪",
        roles: ["admin", "laboratorio"],
        children: [
          { id: "lab-analise", label: "Análise Química (Scavare)", page: `${BASE}/pages/laboratorio/analise.html` },
          { id: "lab-historico", label: "Histórico", page: `${BASE}/pages/laboratorio/historico.html` },
          { id: "lab-relatorios", label: "Relatórios", page: `${BASE}/pages/laboratorio/relatorios.html` },
        ],
      },

      {
        id: "producao",
        label: "Produção",
        icon: "🏗",
        roles: ["admin", "producao"],
        children: [
          { id: "prod-apontamento", label: "Produção Diária", page: `${BASE}/pages/producao/apontamento.html` },
          { id: "prod-eficiencia", label: "Eficiência", page: `${BASE}/pages/producao/eficiencia.html` },
          { id: "prod-relatorios", label: "Relatorios Diário", page: `${BASE}/pages/producao/relatorios.html` },
        ],
      },

            {
        id: "balanca",
        label: "Balança",
        icon: "⚖",
        roles: ["admin", "balanca"],
        children: [
          { id: "bal-entrada", label: "Entrada de material", page: `${BASE}/pages/balanca/bal-entrada.html` },
          { id: "bal-saida", label: "Saida de material", page: `${BASE}/pages/balanca/bal-saida.html` },
          { id: "bal-historico", label: "Histórico / Relatórios", page: `${BASE}/pages/balanca/bal-historico.html` },
          { id: "bal-resumo", label: "Prévia", page: `${BASE}/pages/balanca/bal-resumo.html` },
          { id: "bal-cotas-gestao", label: "Gestão de Cotas", page: `${BASE}/pages/balanca/bal-cotas-gestao.htm` },
        ],
      },

      {
        id: "sst",
        label: "SST",
        icon: "🦺",
        roles: ["admin", "sst"],
        children: [
          { id: "sst-epi", label: "Entrega de EPI", page: `${BASE}/pages/sst/epi.html` },
          { id: "sst-ocorrencia", label: "Registro de Ocorrência", page: `${BASE}/pages/sst/ocorrencia.html` },
          { id: "sst-treinamentos", label: "Treinamentos", page: `${BASE}/pages/sst/treinamentos.html` },
          { id: "sst-funcionarios-epi", label: "Funcionários (Ficha EPI)", page: `${BASE}/pages/sst/funcionarios-epi.html` },

          // NÃO aparecer no menu, mas precisa existir para o botão "Abrir ficha" funcionar
          { id: "sst-ficha-epi", label: "Ficha EPI", page: `${BASE}/pages/sst/ficha-epi.html`, menu: false },
        ],
      },
      
    ],
  },
  {
    group: "Administração",
    items: [
      { id: "admin-users", label: "Usuários", icon: "🔐", roles: ["admin"], page: `${BASE}/pages/admin/admin-user.html` },
      { id: "admin-audit", label: "Auditoria", icon: "🧾", roles: ["admin"], page: FORBIDDEN_PAGE },
    ],
  },
];

function allowed(item) {
  // Se item não tem "roles", fica liberado
  if (!item.roles) return true;

  // Admin entra em tudo
  if (user.roles.includes("admin")) return true;

  // Normaliza roles do item também (garante comparação consistente)
  const itemRoles = item.roles.map(normRole);
  return itemRoles.some((r) => user.roles.includes(r));
}

function flattenAllowedMenu() {
  const list = [];
  MENU.forEach((g) => {
    g.items.forEach((it) => {
      if (!allowed(it)) return;
      if (it.children && it.children.length) {
        it.children.forEach((c) => {
          if (!allowed(c)) return;
          list.push({ ...c, parent: it });
        });
      } else {
        list.push(it);
      }
    });
  });
  return list;
}

function renderMenu() {
  if (!nav) return;
  nav.innerHTML = "";
  
  MENU.forEach((group) => {
   
    const groupItems = group.items.filter(allowed);
    if (!groupItems.length) return;

    const groupWrap = document.createElement("div");
    groupWrap.className = "nav__group";

    const title = document.createElement("div");
    title.className = "nav__groupTitle";
    title.textContent = group.group;
    groupWrap.appendChild(title);

    groupItems.forEach((item) => {
      const row = document.createElement("div");
      row.className = "nav__item";
      row.dataset.route = item.id;

      // (apenas itens permitidos e visíveis no menu)
      const children = (item.children || []).filter((c) => allowed(c) && c.menu !== false);
      const hasChildren = children.length > 0;

      row.innerHTML = `
        <div class="nav__icon">${item.icon || "•"}</div>
        <div class="nav__label">${item.label}</div>
        ${hasChildren ? `<div class="nav__chev">›</div>` : ``}
      `;

      row.addEventListener("click", () => {
        if (hasChildren) {
          row.classList.toggle("is-open");
          return;
        }
        
        location.hash = `#/${item.id}`;
      });

      groupWrap.appendChild(row);

      if (hasChildren) {
        const sub = document.createElement("div");
        sub.className = "subnav";

        children.forEach((child) => {
          const s = document.createElement("div");
          s.className = "subnav__item";
          s.dataset.route = child.id;
          s.textContent = child.label;

          s.addEventListener("click", (ev) => {
            ev.stopPropagation();
            window.__NAV_FROM_SUBMENU__ = true;
            location.hash = `#/${child.id}`;
          });

          sub.appendChild(s);
        });

        groupWrap.appendChild(sub);
      }
    });

    nav.appendChild(groupWrap);
  });
}

function closeAllMenuGroups() {
  document.querySelectorAll(".nav__item.is-open").forEach((el) => el.classList.remove("is-open"));
}

function setActive(routeId) {
  document.querySelectorAll(".nav__item").forEach((el) => {
    el.classList.toggle("is-active", el.dataset.route === routeId);
  });
  document.querySelectorAll(".subnav__item").forEach((el) => {
    el.classList.toggle("is-active", el.dataset.route === routeId);
  });
}

async function loadPage(url) {
  const res = await fetch(url, { cache: "no-store", credentials: "same-origin" });
  if (!res.ok) throw new Error("Falha ao carregar");
  return await res.text();
}

function findRoute(routeId) {
  for (const group of MENU) {
    for (const item of group.items) {
      if (!allowed(item)) continue;

      if (item.id === routeId) return { ...item, path: `/${item.label}` };

      if (item.children) {
        // NOTE: aqui NÃO filtramos por menu, senão a rota some do router
        const child = item.children.find((c) => c.id === routeId && allowed(c));
        if (child) return { ...child, parent: item, path: `/${item.label} / ${child.label}` };
      }
    }
  }
  return null;
}

function loadRouteController(routeId) {
  const v = Date.now(); // cache bust
  const src = `${BASE}/pages/controllers/${routeId}.js?v=${v}`;

  const old = document.getElementById("route-controller");
  if (old) old.remove();

  console.log("[router] carregando controller:", src);

  const s = document.createElement("script");
  s.id = "route-controller";
  s.src = src;

  s.onload = () => console.log("[router] controller carregado:", src);
  s.onerror = () => console.warn("[router] controller NÃO encontrado:", src);

  document.body.appendChild(s);
}

function autoCollapseSidebar() {
  const isMobile = window.matchMedia("(max-width: 980px)").matches;

  if (isMobile) {
    sidebar.classList.remove("is-open");
    overlay.classList.remove("is-visible");
    overlay.setAttribute("aria-hidden", "true");
    return;
  }

  sidebar.classList.add("is-collapsed");
}

function routeTo(routeId) {
  const allowedRoutes = flattenAllowedMenu();
  const fallback = allowedRoutes[0] ? allowedRoutes[0].id : null;

  const route = findRoute(routeId) || (fallback ? findRoute(fallback) : null);
  if (!route) {
    view.innerHTML = `<p>Sem acesso / sem rotas para este perfil.</p>`;
    topbarTitle.textContent = "Sem acesso";
    if (topbarPath) topbarPath.textContent = "/";
    return;
  }

  setActive(route.id);

  topbarTitle.textContent = route.parent ? route.parent.label : route.label;
  if (topbarPath) topbarPath.textContent = route.path && route.path !== "/" ? route.path : "";

  const page = route.page || FORBIDDEN_PAGE;

  loadPage(page)
    .then((html) => {
      view.innerHTML = html;

      requestAnimationFrame(() => {
        loadRouteController(route.id);
      });

      if (window.__NAV_FROM_SUBMENU__) {
        closeAllMenuGroups();
        window.__NAV_FROM_SUBMENU__ = false;
      }

      autoCollapseSidebar();
    })
    .catch(() => (view.innerHTML = `<p>Erro ao carregar a página.</p>`));
}

function getRouteFromHash() {
  const h = location.hash || "";
  // pega só o "id" da rota, ignorando querystring do hash (ex: ?id=2)
  const m = h.match(/^#\/([^/?#]+)/);
  return m ? m[1] : null;
}

function initUserBadge() {
  const name = user.name || "Usuário";
  const avatar = (name.trim()[0] || "U").toUpperCase();

  const userAvatar = document.getElementById("userAvatar");
  const userName = document.getElementById("userName");
  const userRole = document.getElementById("userRole");

  if (userAvatar) userAvatar.textContent = avatar;
  if (userName) userName.textContent = name;
  if (userRole) userRole.textContent = user.roles.includes("admin") ? "ADMIN" : user.role || "";
}

function init() {
  initUserBadge();
  renderMenu();

  const route = getRouteFromHash();
  routeTo(route || "dashboard");

  window.addEventListener("hashchange", () => {
    const r = getRouteFromHash();
    if (r) routeTo(r);
  });
}

init();