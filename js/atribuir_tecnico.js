// Obtém os elementos do modal e botões
const modalCidades = document.getElementById('modalCidades');
const modalTitulo = document.getElementById('modalTitulo');
const listaCidades = document.getElementById('listaCidades');
const botaoVoltar = document.getElementById('botaoVoltar');
const botaoAtribuirTecnico = document.getElementById('botaoAtribuirTecnico');

// Variáveis de estado
let manutencoesPendentes = [];
let isProcessing = false;
let currentFlowType = '';

// A função agora aceita o 'flow_type' como parâmetro
function abrirModalCidades(flow_type) {
    currentFlowType = flow_type;

    const mensagemErro = document.getElementById('mensagem-erro');
    const mensagemSucesso = document.getElementById('mensagem-sucesso');

    if (mensagemErro) mensagemErro.style.display = 'none';
    if (mensagemSucesso) mensagemSucesso.style.display = 'none';

    modalCidades.classList.add('is-active');

    const titulo = flow_type === 'installation' ? 'Cidades com Instalações' : 'Cidades com Manutenções';
    modalTitulo.textContent = titulo;

    listaCidades.innerHTML = '';
    botaoVoltar.style.display = 'none';
    botaoAtribuirTecnico.style.display = 'none';

    fetch(`get_cidades_com_pendencias.php?flow_type=${flow_type}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.cidades && data.cidades.length > 0) {
                data.cidades.forEach(cidade => {
                    const botao = document.createElement('button');
                    botao.classList.add('botao-cidade');
                    botao.textContent = cidade.nome;
                    botao.setAttribute('data-id', cidade.id_cidade);
                    botao.addEventListener('click', () => {
                        buscarItensPendentes(cidade.id_cidade, cidade.nome);
                    });
                    listaCidades.appendChild(botao);
                });
            } else {
                const tipo = currentFlowType === 'installation' ? 'instalações pendentes' : 'manutenções pendentes';
                listaCidades.innerHTML = `<p>Nenhuma cidade com ${tipo} foi encontrada.</p>`;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar as cidades:', error);
            listaCidades.innerHTML = `<p class="erro">Erro ao carregar cidades.</p>`;
        });
}

// Busca os itens pendentes (manutenções ou instalações)
function buscarItensPendentes(id_cidade, nome_cidade) {
    fetch(`get_atribuicoes_pendentes.php?city_id=${id_cidade}&flow_type=${currentFlowType}`)
        .then(response => response.json())
        .then(data => {
            manutencoesPendentes = data.items;
            if (data.success && manutencoesPendentes && manutencoesPendentes.length > 0) {
                exibirItens(manutencoesPendentes, nome_cidade);
            } else {
                const tipo = currentFlowType === 'installation' ? 'instalação pendente' : 'manutenção pendente';
                modalTitulo.textContent = `Pendências em ${nome_cidade}`;
                listaCidades.innerHTML = `<p>Nenhuma ${tipo} encontrada para esta cidade.</p>`;
                botaoVoltar.style.display = 'block';
                botaoAtribuirTecnico.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar pendências:', error);
            listaCidades.innerHTML = `<p class="erro">Erro ao carregar as pendências.</p>`;
            botaoVoltar.style.display = 'block';
        });
}

// Exibe os itens no modal, incluindo o motivo da devolução se houver
function exibirItens(itens, nome_cidade) {
    const titulo = currentFlowType === 'installation' ? 'Instalações Pendentes' : 'Manutenções Pendentes';
    modalTitulo.textContent = `${titulo} em ${nome_cidade}`;

    listaCidades.innerHTML = '';
    botaoVoltar.style.display = 'block';
    botaoAtribuirTecnico.style.display = 'none';

    itens.forEach(item => {
        const botao = document.createElement('button');
        botao.classList.add('botao-manutencao-pendente');
        botao.setAttribute('data-id', item.id_manutencao);

        let textoBotao = `${item.nome_equip} - ${item.referencia_equip}\nOcorrência: ${item.ocorrencia_reparo}`;

        if (item.motivo_devolucao && item.motivo_devolucao.trim() !== '') {
            textoBotao += `\nDevolução: ${item.motivo_devolucao}`;
        }
        
        botao.textContent = textoBotao;
        
        botao.addEventListener('click', () => {
            botao.classList.toggle('selecionado');
            verificarSelecao();
        });

        listaCidades.appendChild(botao);
    });
}

// Verifica se há itens selecionados para mostrar o botão de atribuir
function verificarSelecao() {
    const itensSelecionados = document.querySelectorAll('.botao-manutencao-pendente.selecionado');
    botaoAtribuirTecnico.style.display = itensSelecionados.length > 0 ? 'block' : 'none';
}

// Abre a tela de atribuição de técnico
function atribuirTecnico() {
    const mensagemErro = document.getElementById('mensagem-erro');
    if (mensagemErro) mensagemErro.style.display = 'none';
    if (isProcessing) return;
    isProcessing = true;
    const itensSelecionados = document.querySelectorAll('.botao-manutencao-pendente.selecionado');
    const idsManutencao = Array.from(itensSelecionados).map(item => parseInt(item.getAttribute('data-id'), 10));
    const manutencoesSelecionadas = manutencoesPendentes.filter(m => idsManutencao.includes(parseInt(m.id_manutencao, 10)));
    modalTitulo.textContent = `Atribuir Técnico`;
    listaCidades.innerHTML = '';
    listaCidades.classList.add('detalhe-manutencao-container');
    botaoAtribuirTecnico.style.display = 'none';
    botaoVoltar.style.display = 'block';
    if (manutencoesSelecionadas.length > 0) {
        const manutencoesContainer = document.createElement('div');
        manutencoesContainer.classList.add('manutencoes-flex-container');
        listaCidades.appendChild(manutencoesContainer);
        manutencoesSelecionadas.forEach(manutencao => {
            const divItem = document.createElement('div');
            divItem.classList.add('detalhe-manutencao');
            divItem.setAttribute('data-id', manutencao.id_manutencao);
            divItem.innerHTML = `<p class="titulo-equipamento"><strong>${manutencao.referencia_equip} - ${manutencao.nome_equip}</strong></p><p class="descricao-problema">DESCRIÇÃO PROBLEMA: ${manutencao.ocorrencia_reparo}</p>`;
            manutencoesContainer.appendChild(divItem);
        });
        const tituloData = document.createElement('h3');
        tituloData.textContent = 'Selecione a data para execução';
        tituloData.classList.add('titulo-data');
        listaCidades.appendChild(tituloData);
        const dataContainer = document.createElement('div');
        dataContainer.classList.add('data-container');
        dataContainer.innerHTML = `<div><label for="dataInicio">Data de Início:</label><input type="date" id="dataInicio" class="input-data"></div><div><label for="dataFim">Data de Fim:</label><input type="date" id="dataFim" class="input-data"></div>`;
        listaCidades.appendChild(dataContainer);
        const tituloTecnicos = document.createElement('h3');
        tituloTecnicos.textContent = 'Selecione o(s) Técnico(s)';
        tituloTecnicos.classList.add('titulo-tecnicos');
        listaCidades.appendChild(tituloTecnicos);
        const tecnicosContainer = document.createElement('div');
        tecnicosContainer.classList.add('tecnicos-container');
        listaCidades.appendChild(tecnicosContainer);
        fetch('get_tecnicos.php').then(response => response.json()).then(data => {
            if (data.success && data.tecnicos && data.tecnicos.length > 0) {
                data.tecnicos.forEach(tecnico => {
                    const botaoTecnico = document.createElement('button');
                    botaoTecnico.classList.add('botao-tecnico');
                    botaoTecnico.textContent = tecnico.nome;
                    botaoTecnico.setAttribute('data-id', tecnico.id_tecnico);
                    botaoTecnico.onclick = () => botaoTecnico.classList.toggle('selecionado-tecnico');
                    tecnicosContainer.appendChild(botaoTecnico);
                });
            } else {
                tecnicosContainer.innerHTML = `<p>Nenhum técnico encontrado.</p>`;
            }
        }).catch(error => {
            console.error('Erro ao carregar técnicos:', error);
            tecnicosContainer.innerHTML = `<p class="erro">Erro ao carregar técnicos.</p>`;
        });
        const tituloVeiculos = document.createElement('h3');
        tituloVeiculos.textContent = 'Selecione o(os) Veículo(s)';
        tituloVeiculos.classList.add('titulo-veiculos');
        listaCidades.appendChild(tituloVeiculos);
        const veiculosContainer = document.createElement('div');
        veiculosContainer.classList.add('veiculos-container');
        listaCidades.appendChild(veiculosContainer);
        fetch('get_veiculos.php').then(response => response.json()).then(data => {
            if (data && data.length > 0) {
                data.forEach(veiculo => {
                    const botaoVeiculo = document.createElement('button');
                    botaoVeiculo.classList.add('botao-veiculo');
                    botaoVeiculo.innerHTML = `${veiculo.nome}<br>${veiculo.placa}`;
                    botaoVeiculo.setAttribute('data-id', veiculo.id_veiculo);
                    botaoVeiculo.onclick = () => botaoVeiculo.classList.toggle('selecionado-veiculo');
                    veiculosContainer.appendChild(botaoVeiculo);
                });
            } else {
                veiculosContainer.innerHTML = `<p>Nenhum veículo encontrado.</p>`;
            }
        }).catch(error => {
            console.error('Erro ao carregar veículos:', error);
            veiculosContainer.innerHTML = `<p class="erro">Erro ao carregar veículos.</p>`;
        });
        const botaoContainer = document.createElement('div');
        botaoContainer.classList.add('botao-container');
        const novoBotao = document.createElement('button');
        novoBotao.id = 'botaoAtribuirManutencao';
        novoBotao.classList.add('botao-selecionar-tecnicos');
        novoBotao.textContent = currentFlowType === 'installation' ? 'Atribuir Instalação' : 'Atribuir Manutenção';
        novoBotao.addEventListener('click', atribuirManutencaoCompleto);
        botaoContainer.appendChild(novoBotao);
        listaCidades.appendChild(botaoContainer);
    } else {
        listaCidades.innerHTML = `<p>Nenhuma pendência selecionada para atribuição.</p>`;
    }
    isProcessing = false;
}

// Função final que envia os dados para o servidor
function atribuirManutencaoCompleto() {
    const botaoAtribuir = document.getElementById('botaoAtribuirManutencao');
    const mensagemErro = document.getElementById('mensagem-erro');
    const mensagemSucesso = document.getElementById('mensagem-sucesso');
    if (!botaoAtribuir || !mensagemErro || !mensagemSucesso) {
        console.error('Erro: Elementos HTML não encontrados.');
        return;
    }
    mensagemErro.style.display = 'none';
    mensagemSucesso.style.display = 'none';
    botaoAtribuir.disabled = true;
    botaoAtribuir.textContent = 'Carregando...';
    const idsManutencao = Array.from(document.querySelectorAll('.manutencoes-flex-container .detalhe-manutencao')).map(item => parseInt(item.getAttribute('data-id'), 10));
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    const idsTecnicos = Array.from(document.querySelectorAll('.botao-tecnico.selecionado-tecnico')).map(item => parseInt(item.getAttribute('data-id'), 10));
    const idsVeiculos = Array.from(document.querySelectorAll('.botao-veiculo.selecionado-veiculo')).map(item => parseInt(item.getAttribute('data-id'), 10));
    const camposFaltantes = [];
    if (idsManutencao.length === 0) camposFaltantes.push('Manutenções');
    if (!dataInicio) camposFaltantes.push('Data de Início');
    if (!dataFim) camposFaltantes.push('Data de Fim');
    if (idsTecnicos.length === 0) camposFaltantes.push('Técnicos');
    if (idsVeiculos.length === 0) camposFaltantes.push('Veículos');
    if (dataInicio) {
        const dataAtual = new Date();
        const dataInicioObj = new Date(dataInicio + 'T00:00:00');
        dataAtual.setHours(0, 0, 0, 0);
        if (dataInicioObj < dataAtual) {
            camposFaltantes.push('A data de Início não pode ser anterior à data atual.');
        }
    }
    if (camposFaltantes.length > 0) {
        mensagemErro.textContent = `Por favor, selecione ou preencha o(s) seguinte(s) campo(s): ${camposFaltantes.join(', ')}.`;
        mensagemErro.style.display = 'block';
        botaoAtribuir.disabled = false;
        botaoAtribuir.textContent = currentFlowType === 'installation' ? 'Atribuir Instalação' : 'Atribuir Manutenção';
        return;
    }
    const dataToSend = { idsManutencao, dataInicio, dataFim, idsTecnicos, idsVeiculos };
    fetch('atribuir_tecnicos_manutencao.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataToSend)
    }).then(response => response.json()).then(data => {
        if (data.success) {
            botaoAtribuir.style.display = 'none';
            mensagemSucesso.textContent = 'Atribuição realizada com sucesso!';
            mensagemSucesso.style.display = 'block';
            setTimeout(fecharModalCidades, 3000);
        } else {
            alert('Erro ao atribuir: ' + data.message);
            botaoAtribuir.disabled = false;
            botaoAtribuir.textContent = currentFlowType === 'installation' ? 'Atribuir Instalação' : 'Atribuir Manutenção';
        }
    }).catch(error => {
        console.error('Erro na requisição:', error);
        alert('Erro ao se comunicar com o servidor. Tente novamente.');
        botaoAtribuir.disabled = false;
        botaoAtribuir.textContent = currentFlowType === 'installation' ? 'Atribuir Instalação' : 'Atribuir Manutenção';
    });
}

// Fecha o modal
function fecharModalCidades() {
    modalCidades.classList.remove('is-active');
}

// ADICIONADO PARA CORRIGIR O PROBLEMA DE CLIQUE
// Adiciona os "ouvintes" de clique após o DOM estar completamente carregado
document.addEventListener('DOMContentLoaded', () => {
    // Pega a referência aos botões novamente dentro deste escopo para garantir que eles existam
    const btnManutencoes = document.getElementById('btnManutencoes');
    const btnInstalacoes = document.getElementById('btnInstalacoes');

    // Associa a função abrirModalCidades ao clique de cada botão
    if (btnManutencoes) {
        btnManutencoes.addEventListener('click', () => abrirModalCidades('maintenance'));
    }

    if (btnInstalacoes) {
        btnInstalacoes.addEventListener('click', () => abrirModalCidades('installation'));
    }

    // Evento para fechar o modal clicando na área externa
    window.addEventListener('click', (event) => {
        if (event.target === modalCidades) {
            fecharModalCidades();
        }
    });
    
    // Adiciona o evento de clique ao botão de voltar principal do modal
    if(botaoVoltar) {
        botaoVoltar.addEventListener('click', () => abrirModalCidades(currentFlowType));
    }

    // Adiciona o evento de clique ao botão de atribuir técnico
    if(botaoAtribuirTecnico) {
        botaoAtribuirTecnico.addEventListener('click', atribuirTecnico);
    }
});