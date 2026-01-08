/**
 * ARQUIVO: script.js
 * VERSÃO: 4.5 (UI sempre ATIVA + sincronização silenciosa no login)
 * DESCRIÇÃO:
 *  - Mostra somente os benefícios que o usuário tem direito (entitlements)
 *  - Benefícios sempre aparecem como "Ativo" (sem botão de ativar)
 *  - Ao logar com sucesso, dispara sincronização via /api/sync.php (silent)
 */

const PRODUCT_IDS = {
  clube: "f5686b32-54bf-4759-bd97-934657e61301",
  tele:  "d643411c-98be-495e-9a7f-d1483377aa07"
};

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-check-cpf');
  if (form) form.addEventListener('submit', handleCheckCpf);

  const cpfInput = document.getElementById('cpf');
  if (cpfInput) cpfInput.addEventListener('input', function () { maskCPF(this); });

  const logoutBtn = document.getElementById('btn-logout');
  if (logoutBtn) logoutBtn.addEventListener('click', logout);

  document.addEventListener('keydown', (e) => {
    if (e.key === "Escape") closeModal();
  });
});

function fillTestCpf() {
  const input = document.getElementById('cpf');
  if (!input) return;

  input.value = '123.456.789-00';
  input.focus();
  input.classList.add('ring-4', 'ring-tks-primary/20');
  setTimeout(() => input.classList.remove('ring-4', 'ring-tks-primary/20'), 300);
}

function maskCPF(input) {
  let value = input.value.replace(/\D/g, "");
  if (value.length > 11) value = value.slice(0, 11);
  value = value.replace(/(\d{3})(\d)/, "$1.$2");
  value = value.replace(/(\d{3})(\d)/, "$1.$2");
  value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
  input.value = value;
}

async function handleCheckCpf(e) {
  e.preventDefault();

  const btnText = document.getElementById('btn-text');
  const btnLoader = document.getElementById('btn-loader');
  const btn = document.getElementById('btn-verify');
  const cpfVal = document.getElementById('cpf')?.value || '';

  if (cpfVal.length < 14) return;

  // UI loading
  if (btn) btn.disabled = true;
  if (btnText) btnText.textContent = "Verificando...";
  const arrow = btn?.querySelector('.fa-arrow-right');
  if (arrow) arrow.style.display = 'none';
  if (btnLoader) btnLoader.classList.remove('hidden');

  try {
    const res = await fetch(`/api/activation.php?cpf=${encodeURIComponent(cpfVal)}`, {
      method: "GET",
      headers: { "Accept": "application/json" }
    });

    const data = await res.json();
    console.log("DATA FULL:", data);

    if (!data?.found) {
      openModal();
      return;
    }

    // anima saída login
    const loginScreen = document.getElementById('screen-login');
    if (loginScreen) {
      loginScreen.style.transition = 'all 0.5s ease';
      loginScreen.style.opacity = '0';
      loginScreen.style.transform = 'translateY(-20px)';
    }

    setTimeout(() => {
      // esconde login
      if (loginScreen) loginScreen.classList.add('hidden');

      // mostra dashboard
      const dashboard = document.getElementById('screen-dashboard');
      if (dashboard) {
        dashboard.classList.remove('hidden');
        dashboard.style.display = 'flex';
      }

      const fullName = data?.profile?.full_name || '--';
      const initials = makeInitials(fullName);

      const userNameEl = document.getElementById('user-name');
      if (userNameEl) userNameEl.textContent = fullName;

      const company = data?.company_membership?.company || null;
      const companyName = company?.name || company?.trade_name || company?.corporate_name || '--';
      const userCompanyEl = document.getElementById('user-company');
      if (userCompanyEl) userCompanyEl.textContent = companyName;

      const initialsEl = document.getElementById('user-initials-display');
      if (initialsEl) initialsEl.textContent = initials;

      // ✅ mostra somente benefícios com entitlement ativo
      applyEntitlementsUI(data?.entitlements || []);

      // ✅ sincronização silenciosa (não muda UI)
      const payload = buildSyncPayload(data);
      if (payload?.user_id) runSynchronization(payload);

    }, 400);

  } catch (err) {
    console.error(err);
    openModal();
  } finally {
    // UI reset
    if (btn) btn.disabled = false;
    if (btnText) btnText.textContent = "Continuar";
    if (arrow) arrow.style.display = 'inline-block';
    if (btnLoader) btnLoader.classList.add('hidden');
  }
}

function makeInitials(name) {
  const parts = String(name).trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return '--';
  const first = parts[0][0] || '';
  const last  = parts.length > 1 ? (parts[parts.length - 1][0] || '') : '';
  return (first + last).toUpperCase();
}

/**
 * Mostra apenas os cards que o usuário tem direito
 */
function applyEntitlementsUI(entitlements) {
  const cardClube = document.getElementById('card-clube');
  const cardTele  = document.getElementById('card-tele');

  if (!cardClube || !cardTele) {
    console.error("Cards não encontrados no DOM", { cardClube, cardTele });
    return;
  }

  // 1) esconde tudo
  cardClube.classList.add('hidden');
  cardTele.classList.add('hidden');

  // 2) pega ids ativos
  const ids = (entitlements || [])
    .map(e => String(e?.product?.id || '').trim())
    .filter(Boolean);

  const hasClube = ids.includes(PRODUCT_IDS.clube);
  const hasTele  = ids.includes(PRODUCT_IDS.tele);

  // 3) mostra só o que tem direito
  if (hasClube) cardClube.classList.remove('hidden');
  if (hasTele)  cardTele.classList.remove('hidden');

  // 4) se não tiver nenhum, mostra aviso
  const container = document.querySelector('#screen-dashboard .lg\\:col-span-2');
  if (!container) return;

  const old = document.getElementById('no-benefits');
  if (old) old.remove();

  if (!hasClube && !hasTele) {
    const div = document.createElement('div');
    div.id = 'no-benefits';
    div.className = 'bg-white rounded-[1.5rem] p-6 shadow-clean border border-slate-100 text-slate-600';
    div.innerHTML = `
      <p class="font-bold text-slate-800 mb-1">Nenhum benefício disponível</p>
      <p class="text-sm text-slate-500">Não encontramos benefícios ativos para este CPF.</p>
    `;
    container.appendChild(div);
  }
}

/**
 * Monta payload pra /api/sync.php
 * Ajuste campos conforme seu activation.php retorna.
 */
function buildSyncPayload(activationData) {
  const profile = activationData?.profile || {};
  const company = activationData?.company_membership?.company || {};
  const entitlements = activationData?.entitlements || [];

  // ✅ agora salva NOME do produto (vindo do activation.php)
  const activeProducts = (entitlements || [])
    .map(e => String(e?.product?.name || '').trim())
    .filter(Boolean);

  return {
    user_id: profile?.id,
    cpf: profile?.cpf,
    full_name: profile?.full_name,
    phone: profile?.phone,
    birth_date: profile?.birth_date,
    company: {
      id: company?.id,
      name: company?.name || company?.trade_name || company?.corporate_name || null
    },
    active_products: activeProducts // ✅ nomes
  };
}


/**
 * Dispara sincronização sem afetar UI.
 * Se falhar, só loga no console (pra não expor pro usuário).
 */
async function runSynchronization(payload) {
  try {
    const res = await fetch('/api/sync.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      console.warn('SYNC failed:', res.status, data);
      return { ok: false, data };
    }

    console.log('SYNC ok:', data);
    return { ok: true, data };
  } catch (err) {
    console.warn('SYNC error:', err);
    return { ok: false, data: null };
  }
}

// --- MODAL ERROR ---
function openModal() {
  const modal = document.getElementById('modal-error');
  if (!modal) return;
  modal.classList.remove('hidden');
  setTimeout(() => { modal.classList.add('flex'); }, 10);
}

function closeModal() {
  const modal = document.getElementById('modal-error');
  if (!modal) return;
  modal.classList.remove('flex');
  setTimeout(() => { modal.classList.add('hidden'); }, 300);
}

// --- MODAL SUCCESS (mantido se você ainda usa em outras ações) ---
function openSuccessModal() {
  const modal = document.getElementById('modal-success');
  if (!modal) return;
  modal.classList.remove('hidden');
  setTimeout(() => { modal.classList.add('flex'); }, 10);
}

function closeSuccessModal() {
  const modal = document.getElementById('modal-success');
  if (!modal) return;
  modal.classList.remove('flex');
  setTimeout(() => { modal.classList.add('hidden'); }, 300);
}

function logout() {
  location.reload();
}


document.getElementById('access-club').addEventListener('click', function (e) {
    e.preventDefault();

    const ua = navigator.userAgent || navigator.vendor || window.opera;

    // Android
    if (/android/i.test(ua)) {
        window.location.href = 'https://play.google.com/store/apps/details?id=br.com.tks.vantagens&hl=pt_BR&pli=1';
        return;
    }

    // iOS (iPhone, iPad, iPod)
    if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
        window.location.href = 'https://apps.apple.com/us/app/tks-vantagens/id6459477158?l=pt-BR';
        return;
    }

    // Desktop (Windows, Mac, Linux)
    window.location.href = 'https://app.tksvantagens.com.br/main';
});

