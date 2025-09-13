/**
 * JavaScript para Sistema de Gestão de EPIs Klarbyte
 * Funcionalidades de interface e validação
 */

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    initializeSystem();
});

/**
 * Inicializa o sistema e seus componentes
 */
function initializeSystem() {
    // Inicializar tooltips e outros componentes
    initializeAlerts();
    initializeFilters();
    initializeForms();
    
    // Verificar alertas de estoque e validade
    checkStockAlerts();
    checkExpirationAlerts();
}

/**
 * Inicializa sistema de alertas
 */
function initializeAlerts() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 5000);
    });
}

/**
 * Inicializa filtros de pesquisa
 */
function initializeFilters() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            debounce(filterTable, 300)(this);
        });
    });
}

/**
 * Inicializa validação de formulários
 */
function initializeForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Filtra tabela baseado no input de pesquisa
 */
function filterTable(input) {
    const filter = input.value.toLowerCase();
    const table = input.closest('.card').querySelector('table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

/**
 * Debounce function para otimizar pesquisas
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
 * Valida formulários antes do envio
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Este campo é obrigatório');
            isValid = false;
        } else {
            clearFieldError(field);
        }
        
        // Validações específicas por tipo
        if (field.type === 'email' && field.value) {
            if (!isValidEmail(field.value)) {
                showFieldError(field, 'Email inválido');
                isValid = false;
            }
        }
        
        if (field.type === 'number' && field.value) {
            if (parseFloat(field.value) < 0) {
                showFieldError(field, 'Valor deve ser positivo');
                isValid = false;
            }
        }
        
        if (field.type === 'date' && field.value) {
            const date = new Date(field.value);
            if (date < new Date()) {
                if (field.name === 'validade') {
                    showAlert('warning', 'Atenção: Data de validade no passado');
                }
            }
        }
    });
    
    return isValid;
}

/**
 * Mostra erro em campo específico
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '4px';
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Remove erro de campo
 */
function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Valida formato de email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Verifica alertas de estoque baixo
 */
function checkStockAlerts() {
    const stockCells = document.querySelectorAll('.stock-quantity');
    stockCells.forEach(cell => {
        const current = parseInt(cell.textContent);
        const minimum = parseInt(cell.dataset.minimum || 10);
        
        if (current <= minimum) {
            cell.classList.add('badge', 'badge-danger');
            cell.title = 'Estoque baixo!';
        } else if (current <= minimum * 1.5) {
            cell.classList.add('badge', 'badge-warning');
            cell.title = 'Atenção ao estoque';
        }
    });
}

/**
 * Verifica alertas de validade próxima
 */
function checkExpirationAlerts() {
    const expirationCells = document.querySelectorAll('.expiration-date');
    const today = new Date();
    const thirtyDaysFromNow = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));
    
    expirationCells.forEach(cell => {
        const expirationDate = new Date(cell.textContent);
        
        if (expirationDate < today) {
            cell.classList.add('badge', 'badge-danger');
            cell.title = 'Vencido!';
        } else if (expirationDate <= thirtyDaysFromNow) {
            cell.classList.add('badge', 'badge-warning');
            cell.title = 'Vence em breve';
        }
    });
}

/**
 * Mostra alerta na tela
 */
function showAlert(type, message, timeout = 5000) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fade-in`;
    alertDiv.textContent = message;
    
    // Inserir no topo da página
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remover após timeout
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 300);
    }, timeout);
}

/**
 * Confirma ação antes de executar
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Formata data para exibição brasileira
 */
function formatDateBR(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

/**
 * Formata valor monetário
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Adiciona máscara para CPF/CNPJ
 */
function addDocumentMask(input) {
    input.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        
        if (value.length <= 11) {
            // CPF
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            // CNPJ
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        }
        
        this.value = value;
    });
}

/**
 * Adiciona máscara para telefone
 */
function addPhoneMask(input) {
    input.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        
        if (value.length <= 10) {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
        }
        
        this.value = value;
    });
}

/**
 * Inicializa máscaras nos campos
 */
function initializeMasks() {
    // CPF/CNPJ
    const documentInputs = document.querySelectorAll('input[name*="cpf"], input[name*="cnpj"]');
    documentInputs.forEach(addDocumentMask);
    
    // Telefone
    const phoneInputs = document.querySelectorAll('input[name*="telefone"], input[type="tel"]');
    phoneInputs.forEach(addPhoneMask);
}

/**
 * Modal simples para exibir detalhes
 */
function showModal(title, content) {
    // Criar overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    `;
    
    // Criar modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    `;
    
    modal.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3>${title}</h3>
            <button onclick="this.closest('.modal-overlay').remove()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <div>${content}</div>
    `;
    
    overlay.className = 'modal-overlay';
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Fechar ao clicar no overlay
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
}

/**
 * Carrega conteúdo via AJAX
 */
function loadContent(url, containerId, callback) {
    fetch(url)
        .then(response => response.text())
        .then(data => {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = data;
                if (callback) callback();
            }
        })
        .catch(error => {
            console.error('Erro ao carregar conteúdo:', error);
            showAlert('danger', 'Erro ao carregar dados');
        });
}

/**
 * Atualiza contador em tempo real
 */
function updateCounter(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    
    if (input && counter) {
        input.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            counter.textContent = `${remaining} caracteres restantes`;
            
            if (remaining < 0) {
                counter.style.color = '#dc3545';
            } else if (remaining < 20) {
                counter.style.color = '#ffc107';
            } else {
                counter.style.color = '#6c757d';
            }
        });
    }
}

// Inicializar máscaras quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', initializeMasks);