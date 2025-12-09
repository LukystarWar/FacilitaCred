/**
 * Facilita Cred - Main JavaScript
 * Funções globais e utilitárias
 */

// ============================================
// MODAL FUNCTIONS
// ============================================

/**
 * Abre um modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Previne scroll do body
    }
}

/**
 * Fecha um modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // Restaura scroll do body
    }
}

/**
 * Fecha modal ao clicar fora dele
 */
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeModal(e.target.id);
    }
});

/**
 * Fecha modal ao pressionar ESC
 */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal) {
            closeModal(activeModal.id);
        }
    }
});

// ============================================
// FORM VALIDATION
// ============================================

/**
 * Valida CPF
 */
function validateCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');

    if (cpf.length !== 11) return false;

    // Verifica se todos os dígitos são iguais
    if (/^(\d)\1{10}$/.test(cpf)) return false;

    // Validação do primeiro dígito
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let digit = 11 - (sum % 11);
    if (digit >= 10) digit = 0;
    if (digit !== parseInt(cpf.charAt(9))) return false;

    // Validação do segundo dígito
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    digit = 11 - (sum % 11);
    if (digit >= 10) digit = 0;
    if (digit !== parseInt(cpf.charAt(10))) return false;

    return true;
}

/**
 * Formata CPF enquanto digita
 */
function formatCPF(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substring(0, 11);

    if (value.length > 9) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (value.length > 3) {
        value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }

    input.value = value;
}

/**
 * Formata telefone enquanto digita
 */
function formatPhone(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substring(0, 11);

    if (value.length > 10) {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (value.length > 6) {
        value = value.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
    } else if (value.length > 2) {
        value = value.replace(/(\d{2})(\d{1,5})/, '($1) $2');
    }

    input.value = value;
}

/**
 * Formata moeda (R$)
 */
function formatMoney(input) {
    let value = input.value.replace(/\D/g, '');
    value = (parseInt(value) / 100).toFixed(2);
    value = value.replace('.', ',');
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = value ? 'R$ ' + value : '';
}

/**
 * Formata data (dd/mm/yyyy)
 */
function formatDate(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 8) value = value.substring(0, 8);

    if (value.length > 4) {
        value = value.replace(/(\d{2})(\d{2})(\d{1,4})/, '$1/$2/$3');
    } else if (value.length > 2) {
        value = value.replace(/(\d{2})(\d{1,2})/, '$1/$2');
    }

    input.value = value;
}

// ============================================
// CONFIRMATIONS
// ============================================

/**
 * Confirma uma ação
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Confirma exclusão
 */
function confirmDelete(itemName, callback) {
    const message = `Tem certeza que deseja excluir "${itemName}"?\n\nEsta ação não pode ser desfeita.`;
    confirmAction(message, callback);
}

// ============================================
// AJAX HELPERS
// ============================================

/**
 * Faz uma requisição POST via AJAX
 */
async function ajaxPost(url, data) {
    try {
        const formData = new FormData();
        for (const key in data) {
            formData.append(key, data[key]);
        }

        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        return await response.json();
    } catch (error) {
        console.error('Erro na requisição:', error);
        return { success: false, message: 'Erro ao processar requisição' };
    }
}

/**
 * Faz uma requisição GET via AJAX
 */
async function ajaxGet(url) {
    try {
        const response = await fetch(url);
        return await response.json();
    } catch (error) {
        console.error('Erro na requisição:', error);
        return { success: false, message: 'Erro ao processar requisição' };
    }
}

// ============================================
// UTILITIES
// ============================================

/**
 * Debounce function (útil para search inputs)
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Auto-hide alerts após alguns segundos
 */
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

/**
 * Sidebar toggle para mobile
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// ============================================
// LOAN CALCULATION HELPERS
// ============================================

/**
 * Calcula juros
 */
function calculateInterest(amount, installments) {
    const INTEREST_SINGLE = 20; // 20% à vista
    const INTEREST_INSTALLMENT = 15; // 15% ao mês

    if (installments === 1) {
        return amount * (INTEREST_SINGLE / 100);
    } else {
        const rate = INTEREST_INSTALLMENT * installments;
        return amount * (rate / 100);
    }
}

/**
 * Calcula total com juros
 */
function calculateTotalWithInterest(amount, installments) {
    const interest = calculateInterest(amount, installments);
    return amount + interest;
}

/**
 * Calcula valor de cada parcela
 */
function calculateInstallmentValue(totalAmount, installments) {
    return totalAmount / installments;
}

// ============================================
// CONSOLE INFO
// ============================================

console.log('%c💰 Facilita Cred', 'font-size: 20px; font-weight: bold; color: #2563eb;');
console.log('%cSistema de Gestão de Empréstimos v1.0.0', 'color: #6b7280;');
