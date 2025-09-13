// =============================================================================
// 1. VARIÁVEIS GLOBAIS E FUNÇÕES AUXILIARES
// =============================================================================

let dadosProvedores = [];
// --- VARIÁVEIS PARA AUTO-UPDATE ---
let currentChecksum = null;
let updateTimeoutId = null;
const BASE_INTERVAL = 15000; // 15 segundos
const MAX_INTERVAL = 120000; // 2 minutos
let currentInterval = BASE_INTERVAL;

function exibirMensagem(elemento, mensagem, tipo) {
    elemento.textContent = mensagem;
    elemento.className = `mensagem ${tipo}`;
    elemento.style.display = 'block';
}

function alternarCarregamento(botao, spinner, mostrar) {
    const botaoTexto = botao.querySelector('span:not(.carregando)');
    if (mostrar) {
        botao.disabled = true;
        spinner.style.display = 'block';
        if (botaoTexto) botaoTexto.style.display = 'none';
    } else {
        botao.disabled = false;
        spinner.style.display = 'none';
        if (botaoTexto) botaoTexto.style.display = 'block';
    }
}

function fecharModalEdicaoProvedor() {
    document.getElementById('modalEdicaoProvedor').classList.remove('esta-ativo');
}

function fecharModalAdicionarProvedor() {
    document.getElementById('modalAdicionarProvedor').classList.remove('esta-ativo');
}

async function carregarCidadesNosModais() {
    const selects = [
        document.getElementById('cidadeProvedorAdicionar'),
        document.getElementById('cidadeProvedorEdicao')
    ];
    try {
        const response = await fetch('API/get_cidades.php');
        const data = await response.json();
        selects.forEach(select => {
            select.innerHTML = '<option value="">Selecione a Cidade</option>';
            if (data.success) {
                data.cidades.forEach(cidade => {
                    const option = document.createElement('option');
                    option.value = cidade.id_cidade;
                    option.textContent = cidade.nome;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">Erro ao carregar cidades</option>';
            }
        });
    } catch (error) {
        console.error('Erro ao buscar cidades:', error);
        selects.forEach(select => {
            select.innerHTML = '<option value="">Erro de conexão</option>';
        });
    }
}

function abrirModalAdicionarProvedor() {
    const formulario = document.getElementById('formularioAdicionarProvedor');
    formulario.reset();
    document.getElementById('mensagemAdicionarProvedor').style.display = 'none';
    const botao = formulario.querySelector('.botao-salvar');
    const spinner = document.getElementById('carregandoAdicionarProvedor');
    alternarCarregamento(botao, spinner, false);
    botao.style.display = 'flex';
    document.getElementById('modalAdicionarProvedor').classList.add('esta-ativo');
}

function abrirModalEdicaoProvedor(idProvedor) {
    const provedor = dadosProvedores.find(p => p.id_provedor == idProvedor);
    if (provedor) {
        document.getElementById('idProvedorEdicao').value = provedor.id_provedor;
        document.getElementById('nomeProvedorEdicao').value = provedor.nome_prov;
        document.getElementById('cidadeProvedorEdicao').value = provedor.id_cidade;
        const formulario = document.getElementById('formularioEdicaoProvedor');
        const botao = formulario.querySelector('.botao-salvar');
        const spinner = document.getElementById('carregandoEdicaoProvedor');
        alternarCarregamento(botao, spinner, false);
        botao.style.display = 'flex';
        document.getElementById('mensagemEdicaoProvedor').style.display = 'none';
        document.getElementById('modalEdicaoProvedor').classList.add('esta-ativo');
    } else {
        alert('Provedor não encontrado.');
    }
}

async function buscarEExibirProvedores(termoPesquisa = '') {
    const containerListaProvedores = document.getElementById('containerListaProvedores');
    if (!currentChecksum) {
        containerListaProvedores.innerHTML = 'Carregando provedores...';
    }
    try {
        const url = new URL('API/get_provedor.php', window.location.href);
        url.searchParams.set('search', termoPesquisa);
        const response = await fetch(url);
        const data = await response.json();
        if (data.success) {
            currentChecksum = data.checksum;
            dadosProvedores = data.providers;
            exibirListaProvedores(dadosProvedores);
        } else {
            containerListaProvedores.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Erro ao buscar provedores:', error);
        containerListaProvedores.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar os provedores.</div>`;
    }
}

function exibirListaProvedores(provedores) {
    const containerListaProvedores = document.getElementById('containerListaProvedores');
    if (provedores.length === 0) {
        containerListaProvedores.innerHTML = '<div class="mensagem">Nenhum provedor encontrado.</div>';
        return;
    }
    const htmlListaProvedores = provedores.map(provedor => `
        <div class="item-provedor">
            <h3>${provedor.nome_prov}</h3>
            <p><strong>Cidade:</strong> ${provedor.cidade_prov || 'N/A'}</p>
            <button class="botao-editar" onclick="abrirModalEdicaoProvedor(${provedor.id_provedor})">Editar</button>
        </div>
    `).join('');
    containerListaProvedores.innerHTML = `<div class="lista-provedores">${htmlListaProvedores}</div>`;
}

async function scheduleNextCheck() {
    if (updateTimeoutId) {
        clearTimeout(updateTimeoutId);
    }
    try {
        const checkResponse = await fetch('API/check_updates.php?context=provedores');
        const checkResult = await checkResponse.json();
        if (checkResult.success && checkResult.checksum !== currentChecksum) {
            console.log('Novas atualizações de provedores detectadas. Recarregando...');
            await buscarEExibirProvedores(document.getElementById('campoPesquisa').value);
            currentInterval = BASE_INTERVAL;
            console.log('Intervalo de verificação de provedores resetado.');
        } else {
            currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
            console.log(`Nenhuma atualização. Próxima verificação de provedores em ${currentInterval / 1000}s.`);
        }
    } catch (error) {
        console.error('Erro no ciclo de verificação de atualizações:', error);
        currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
    } finally {
        updateTimeoutId = setTimeout(scheduleNextCheck, currentInterval);
    }
}

// =============================================================================
// 2. EXECUÇÃO E EVENT LISTENERS
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('campoPesquisa').addEventListener('input', (evento) => {
        buscarEExibirProvedores(evento.target.value);
    });

    document.getElementById('formularioEdicaoProvedor').addEventListener('submit', async function(evento) {
        evento.preventDefault();
        const mensagem = document.getElementById('mensagemEdicaoProvedor');
        const botao = this.querySelector('.botao-salvar');
        const spinner = document.getElementById('carregandoEdicaoProvedor');
        alternarCarregamento(botao, spinner, true);
        const dadosFormulario = new FormData(this);
        const provedor = Object.fromEntries(dadosFormulario.entries());
        provedor.id_provedor = document.getElementById('idProvedorEdicao').value;
        try {
            const response = await fetch('API/update_provedor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(provedor)
            });
            const data = await response.json();
            if (data.success) {
                exibirMensagem(mensagem, 'Provedor salvo com sucesso!', 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalEdicaoProvedor();
                    // **CORREÇÃO AQUI:** Força a atualização da lista imediatamente.
                    buscarEExibirProvedores(document.getElementById('campoPesquisa').value);
                }, 1500);
            } else {
                exibirMensagem(mensagem, data.message || 'Erro ao atualizar.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, spinner, false);
        }
    });

    document.getElementById('formularioAdicionarProvedor').addEventListener('submit', async function(evento) {
        evento.preventDefault();
        const mensagem = document.getElementById('mensagemAdicionarProvedor');
        const botao = this.querySelector('.botao-salvar');
        const spinner = document.getElementById('carregandoAdicionarProvedor');
        alternarCarregamento(botao, spinner, true);
        const dadosFormulario = new FormData(this);
        const provedor = Object.fromEntries(dadosFormulario.entries());
        try {
            const response = await fetch('API/add_provedor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(provedor)
            });
            const data = await response.json();
            if (data.success) {
                exibirMensagem(mensagem, 'Provedor adicionado com sucesso!', 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalAdicionarProvedor();
                    // **CORREÇÃO AQUI:** Força a atualização da lista imediatamente.
                    buscarEExibirProvedores(document.getElementById('campoPesquisa').value);
                }, 1500);
            } else {
                exibirMensagem(mensagem, data.message || 'Erro ao adicionar.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, spinner, false);
        }
    });

    window.onclick = function(evento) {
        if (evento.target == document.getElementById('modalEdicaoProvedor')) {
            fecharModalEdicaoProvedor();
        }
        if (evento.target == document.getElementById('modalAdicionarProvedor')) {
            fecharModalAdicionarProvedor();
        }
    };

    // INICIALIZAÇÃO
    buscarEExibirProvedores().then(() => {
        console.log('Carga inicial de provedores completa. Iniciando ciclo de verificação.');
        scheduleNextCheck();
    });
    carregarCidadesNosModais();
});