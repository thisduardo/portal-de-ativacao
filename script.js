/**
 * ARQUIVO: script.js
 * VERSÃO: 4.2 (fix: mostrar somente benefícios que o usuário tem direito)
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
 * Observação: seus cards estão com class="hidden" no HTML,
 * então aqui a gente remove/adiciona "hidden" (Tailwind).
 */
function applyEntitlementsUI(entitlements) {
  const cardClube = document.getElementById('card-clube');
  const cardTele  = document.getElementById('card-tele');

  console.log("applyEntitlementsUI called. entitlements:", entitlements);

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

  console.log("IDs ativos:", ids);

  const hasClube = ids.includes(PRODUCT_IDS.clube);
  const hasTele  = ids.includes(PRODUCT_IDS.tele);

  console.log("hasClube:", hasClube, "hasTele:", hasTele);

  // 3) mostra só o que tem direito
  if (hasClube) cardClube.classList.remove('hidden');
  if (hasTele)  cardTele.classList.remove('hidden');

  // 4) se não tiver nenhum, mostra aviso
  if (!hasClube && !hasTele) {
    const container = document.querySelector('#screen-dashboard .lg\\:col-span-2');
    if (container) {
      const old = document.getElementById('no-benefits');
      if (old) old.remove();

      const div = document.createElement('div');
      div.id = 'no-benefits';
      div.className = 'bg-white rounded-[1.5rem] p-6 shadow-clean border border-slate-100 text-slate-600';
      div.innerHTML = `
        <p class="font-bold text-slate-800 mb-1">Nenhum benefício disponível</p>
        <p class="text-sm text-slate-500">Não encontramos benefícios ativos para este CPF.</p>
      `;
      container.appendChild(div);
    }
  } else {
    const old = document.getElementById('no-benefits');
    if (old) old.remove();
  }
}

function triggerCardAction(type) {
  if (type === 'tele') return;
  if (type === 'clube') {
    const btn = document.querySelector('#status-clube-area button');
    if (btn && !btn.disabled) activateBenefit(btn);
  }
}

function activateBenefit(btnElement) {
  // evita propagação se chamado por clique interno
  if (window.event) window.event.stopPropagation();

  const originalWidth = btnElement.offsetWidth;
  btnElement.style.width = originalWidth + 'px';
  btnElement.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
  btnElement.disabled = true;

  setTimeout(() => {
    openSuccessModal();

    const parent = btnElement.parentElement;
    parent.innerHTML = `
      <div class="flex items-center gap-4 anim-fade-in">
        <div class="inline-flex items-center gap-2 text-green-600 font-bold text-sm bg-green-50 px-4 py-2 rounded-lg border border-green-100 shadow-sm">
          <i class="fas fa-check-circle"></i> Ativo
        </div>
        <a href="#" class="text-sm font-semibold text-slate-400 hover:text-tks-primary transition border-b-2 border-transparent hover:border-tks-primaryIMARY pb-0.5">
          Acessar Clube
        </a>
      </div>
    `;
  }, 1500);
}

// --- MODAIS ---
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