let dadosCidades = [];

function exibirMensagem(elemento, mensagem, tipo) {
    elemento.textContent = mensagem;
    elemento.className = `mensagem ${tipo}`;
    elemento.style.display = 'block';
}

function alternarCarregamento(botao, spinner, mostrar) {
    const botaoTexto = botao.querySelector('span');
    if (mostrar) {
        botao.disabled = true;
        if (spinner) spinner.style.display = 'block';
        const textoBotao = botao.querySelector('span:not(.carregando)');
        if (textoBotao) textoBotao.style.display = 'none';
    } else {
        botao.disabled = false;
        if (spinner) spinner.style.display = 'none';
        const textoBotao = botao.querySelector('span:not(.carregando)');
        if (textoBotao) textoBotao.style.display = 'block';
    }
}

function fecharModalAdicionarCidade() {
    document.getElementById('modalAdicionarCidade').classList.remove('esta-ativo');
    document.getElementById('mensagemAdicionarCidade').style.display = 'none';
    document.getElementById('addValidationMessage').style.display = 'none';
    document.getElementById('formularioAdicionarCidade').reset();
}

function fecharModalEdicaoCidade() {
    document.getElementById('modalEdicaoCidade').classList.remove('esta-ativo');
    document.getElementById('mensagemEdicaoCidade').style.display = 'none';
    document.getElementById('editValidationMessage').style.display = 'none';
    document.getElementById('formularioEdicaoCidade').reset();
}

function abrirModalAdicionarCidade() {
    const formulario = document.getElementById('formularioAdicionarCidade');
    const botao = formulario.querySelector('.botao-salvar');
    const spinner = document.getElementById('carregandoAdicionarCidade');
    
    formulario.reset();
    alternarCarregamento(botao, spinner, false);
    document.getElementById('mensagemAdicionarCidade').style.display = 'none';
    document.getElementById('addValidationMessage').style.display = 'none';
    document.getElementById('modalAdicionarCidade').classList.add('esta-ativo');
}

function abrirModalEdicaoCidade(id) {
    const cidade = dadosCidades.find(c => c.id_cidade == id);
    if (cidade) {
        const formulario = document.getElementById('formularioEdicaoCidade');
        const botao = formulario.querySelector('.botao-salvar');
        const spinner = document.getElementById('carregandoEdicaoCidade');

        alternarCarregamento(botao, spinner, false);
        document.getElementById('mensagemEdicaoCidade').style.display = 'none';
        document.getElementById('editValidationMessage').style.display = 'none';

        document.getElementById('idCidadeEdicao').value = cidade.id_cidade;
        document.getElementById('nomeCidadeEdicao').value = cidade.nome;
        document.getElementById('siglaCidadeEdicao').value = cidade.sigla_cidade || '';
        document.getElementById('codCidadeEdicao').value = cidade.cod_cidade || '';

        // LÓGICA ATUALIZADA: Marca os checkboxes corretos
        document.getElementById('editSemaforica').checked = cidade.semaforica == 1;
        document.getElementById('editRadares').checked = cidade.radares == 1;

        document.getElementById('modalEdicaoCidade').classList.add('esta-ativo');
    } else {
        alert('Cidade não encontrada.');
    }
}

// Função de exibição ATUALIZADA
function exibirListaCidades(cidades) {
    const containerListaCidades = document.getElementById('containerListaCidades');
    if (cidades.length === 0) {
        containerListaCidades.innerHTML = '<div class="mensagem">Nenhuma cidade encontrada.</div>';
        return;
    }

    const htmlListaCidades = cidades.map(cidade => {
        let manutencao = [];
        if (cidade.semaforica == 1) manutencao.push('Semáforo');
        if (cidade.radares == 1) manutencao.push('Radar');
        
        return `
            <div class="item-cidade">
                <h3>${cidade.nome} (${cidade.sigla_cidade || 'N/A'})</h3>
                <p><strong>Código:</strong> ${cidade.cod_cidade || 'N/A'}</p>
                <p><strong>Manutenção para:</strong> ${manutencao.join(', ') || 'Nenhuma'}</p>
                <div class="btn-group">
                    <button class="botao-editar" onclick="abrirModalEdicaoCidade(${cidade.id_cidade})">Editar</button>
                    <button class="botao-excluir" onclick="excluirCidade(${cidade.id_cidade})">Excluir</button>
                </div>
            </div>
        `;
    }).join('');

    containerListaCidades.innerHTML = `<div class="lista-cidades">${htmlListaCidades}</div>`;
}

async function buscarEExibirCidades() {
    const containerListaCidades = document.getElementById('containerListaCidades');
    containerListaCidades.innerHTML = 'Carregando cidades...';

    try {
        const response = await fetch('API/get_cidades.php');
        const data = await response.json();
        
        if (data.success) {
            dadosCidades = data.cidades;
            exibirListaCidades(dadosCidades);
        } else {
            containerListaCidades.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Erro ao buscar cidades:', error);
        containerListaCidades.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar as cidades.</div>`;
    }
}

async function excluirCidade(id) {
    if (confirm('Tem certeza que deseja excluir esta cidade?')) {
        try {
            const response = await fetch('API/delete_city.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cidade: id })
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Cidade excluída com sucesso!');
                buscarEExibirCidades();
            } else {
                alert(result.message || 'Erro ao excluir cidade.');
            }
        } catch (error) {
            console.error('Erro ao excluir cidade:', error);
            alert('Ocorreu um erro ao excluir a cidade. Tente novamente.');
        }
    }
}

// Event listener para Adicionar (ATUALIZADO)
document.getElementById('formularioAdicionarCidade').addEventListener('submit', async function(e) {
    e.preventDefault();
    const mensagem = document.getElementById('mensagemAdicionarCidade');
    const validationMessage = document.getElementById('addValidationMessage');
    const botao = this.querySelector('.botao-salvar');
    const spinner = document.getElementById('carregandoAdicionarCidade');

    const semaforicaChecked = document.getElementById('addSemaforica').checked;
    const radaresChecked = document.getElementById('addRadares').checked;

    if (!semaforicaChecked && !radaresChecked) {
        validationMessage.style.display = 'block';
        return;
    }
    validationMessage.style.display = 'none';

    alternarCarregamento(botao, spinner, true);

    const data = {
        nome: document.getElementById('nomeCidadeAdicionar').value,
        sigla_cidade: document.getElementById('siglaCidadeAdicionar').value,
        cod_cidade: document.getElementById('codCidadeAdicionar').value,
        semaforica: semaforicaChecked ? 1 : 0,
        radares: radaresChecked ? 1 : 0
    };

    try {
        const response = await fetch('API/add_city.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            exibirMensagem(mensagem, 'Cidade adicionada com sucesso!', 'sucesso');
            setTimeout(() => {
                fecharModalAdicionarCidade();
                buscarEExibirCidades();
            }, 1500);
        } else {
            exibirMensagem(mensagem, result.message || 'Erro ao adicionar cidade.', 'erro');
            alternarCarregamento(botao, spinner, false);
        }
    } catch (error) {
        exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
        alternarCarregamento(botao, spinner, false);
    }
});

// Event listener para Editar (ATUALIZADO)
document.getElementById('formularioEdicaoCidade').addEventListener('submit', async function(e) {
    e.preventDefault();
    const mensagem = document.getElementById('mensagemEdicaoCidade');
    const validationMessage = document.getElementById('editValidationMessage');
    const botao = this.querySelector('.botao-salvar');
    const spinner = document.getElementById('carregandoEdicaoCidade');
    
    const semaforicaChecked = document.getElementById('editSemaforica').checked;
    const radaresChecked = document.getElementById('editRadares').checked;

    if (!semaforicaChecked && !radaresChecked) {
        validationMessage.style.display = 'block';
        return;
    }
    validationMessage.style.display = 'none';

    alternarCarregamento(botao, spinner, true);
    
    const data = {
        id_cidade: document.getElementById('idCidadeEdicao').value,
        nome: document.getElementById('nomeCidadeEdicao').value,
        sigla_cidade: document.getElementById('siglaCidadeEdicao').value,
        cod_cidade: document.getElementById('codCidadeEdicao').value,
        semaforica: semaforicaChecked ? 1 : 0,
        radares: radaresChecked ? 1 : 0
    };

    try {
        const response = await fetch('API/update_city.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
            exibirMensagem(mensagem, 'Cidade atualizada com sucesso!', 'sucesso');
            setTimeout(() => {
                fecharModalEdicaoCidade();
                buscarEExibirCidades();
            }, 1500);
        } else {
            exibirMensagem(mensagem, result.message || 'Erro ao atualizar cidade.', 'erro');
            alternarCarregamento(botao, spinner, false);
        }
    } catch (error) {
        exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
        alternarCarregamento(botao, spinner, false);
    }
});

window.onclick = function(event) {
    const addModal = document.getElementById('modalAdicionarCidade');
    const editModal = document.getElementById('modalEdicaoCidade');
    if (event.target == addModal) fecharModalAdicionarCidade();
    if (event.target == editModal) fecharModalEdicaoCidade();
};

document.addEventListener('DOMContentLoaded', buscarEExibirCidades);