// Referências para elementos da DOM
const listaManutencoes = document.getElementById('listaManutencoes');
const mensagemCarregamento = document.getElementById('mensagemCarregamento');
const mensagemErro = document.getElementById('mensagemErro');


// Referências para o modal de conclusão de reparo
const concluirReparoModal = document.getElementById('concluirReparoModal');
const nomeEquipamentoModal = document.getElementById('nomeEquipamentoModal');
const referenciaEquipamentoModal = document.getElementById('referenciaEquipamentoModal');
const ocorrenciaReparoModal = document.getElementById('ocorrenciaReparoModal');
const reparoRealizadoTextarea = document.getElementById('reparoRealizadoTextarea');
const materiaisUtilizadosInput = document.getElementById('materiaisUtilizadosInput');
const checkboxNenhumMaterial = document.getElementById('checkboxNenhumMaterial');
const confirmConcluirReparoBtn = document.getElementById('confirmConcluirReparoBtn');
const concluirReparoSpinner = document.getElementById('concluirReparoSpinner');
const concluirReparoMessage = document.getElementById('concluirReparoMessage');

// NOVO: Referências para os botões de rompimento de lacre e campos
const botaoSimRompimento = document.getElementById('botaoSimRompimento');
const botaoNaoRompimento = document.getElementById('botaoNaoRompimento');
const camposRompimentoLacre = document.getElementById('camposRompimentoLacre');
const inputNumeroLacre = document.getElementById('inputNumeroLacre');
const inputInfoRompimento = document.getElementById('inputInfoRompimento');
const inputDataRompimento = document.getElementById('inputDataRompimento');

// Referências para o modal de devolução
const devolucaoModal = document.getElementById('devolucaoModal');
const nomeEquipamentoDevolucaoModal = document.getElementById('nomeEquipamentoDevolucaoModal');
const referenciaEquipamentoDevolucaoModal = document.getElementById('referenciaEquipamentoDevolucaoModal');
const ocorrenciaReparoDevolucaoModal = document.getElementById('ocorrenciaReparoDevolucaoModal');
const textareaDevolucao = document.getElementById('textareaDevolucao');
const botaoConfirmarDevolucao = document.getElementById('botaoConfirmarDevolucao');
const spinnerDevolucao = document.getElementById('spinnerDevolucao');
const mensagemDevolucao = document.getElementById('mensagemDevolucao');

let currentManutencaoId = null;

// Funções de utilidade
function showMessage(element, msg, type) {
    element.textContent = msg;
    element.className = `mensagem ${type}`;
    element.classList.remove('oculto');
}

function hideMessage(element) {
    element.classList.add('oculto');
    element.textContent = '';
}

function toggleSpinner(button, spinner, show) {
    if (show) {
        spinner.classList.remove('oculto');
        button.disabled = true;
    } else {
        spinner.classList.add('oculto');
        button.disabled = false;
    }
}

function calcularDiasEntreDatas(inicio, fim) {
    if (!inicio || !fim) {
        return 0;
    }

    // Cria as datas e zera o horário para garantir que a diferença seja de dias inteiros
    const dataInicio = new Date(inicio);
    dataInicio.setHours(0, 0, 0, 0);

    const dataFim = new Date(fim);
    dataFim.setHours(0, 0, 0, 0);

    // Calcula a diferença em milissegundos
    const diferencaTempo = dataFim.getTime() - dataInicio.getTime();

    // Converte a diferença para dias e arredonda para evitar problemas com fuso horário
    const umDiaEmMilissegundos = 1000 * 60 * 60 * 24;
    const diferencaDias = Math.round(diferencaTempo / umDiaEmMilissegundos);

    // Adiciona 1 ao resultado para ser inclusivo (contar o dia de início)
    return diferencaDias + 1;
}

// Funções para os modais
function openConcluirReparoModal(manutencao) {
    currentManutencaoId = manutencao.id_manutencao;
    reparoRealizadoTextarea.value = '';
    materiaisUtilizadosInput.value = '';
    materiaisUtilizadosInput.disabled = false;
    checkboxNenhumMaterial.checked = false;
    hideMessage(concluirReparoMessage);

    // Reseta o estado do rompimento de lacre
    botaoNaoRompimento.classList.add('ativo');
    botaoSimRompimento.classList.remove('ativo');
    camposRompimentoLacre.classList.add('oculto');
    inputNumeroLacre.value = '';
    inputInfoRompimento.value = '';
    inputDataRompimento.value = '';

    nomeEquipamentoModal.textContent = manutencao.nome_equip;
    referenciaEquipamentoModal.textContent = manutencao.referencia_equip;
    ocorrenciaReparoModal.textContent = manutencao.ocorrencia_reparo || 'N/A';

    concluirReparoModal.classList.add('ativo');
    confirmConcluirReparoBtn.classList.remove('oculto');
    toggleSpinner(confirmConcluirReparoBtn, concluirReparoSpinner, false);
}

function closeConcluirReparoModal() {
    concluirReparoModal.classList.remove('ativo');
}

function openDevolucaoModal(manutencao) {
    currentManutencaoId = manutencao.id_manutencao;
    textareaDevolucao.value = '';
    hideMessage(mensagemDevolucao);

    nomeEquipamentoDevolucaoModal.textContent = manutencao.nome_equip;
    referenciaEquipamentoDevolucaoModal.textContent = manutencao.referencia_equip;
    ocorrenciaReparoDevolucaoModal.textContent = manutencao.ocorrencia_reparo || 'N/A';

    devolucaoModal.classList.add('ativo');
    toggleSpinner(botaoConfirmarDevolucao, spinnerDevolucao, false);
}

function closeDevolucaoModal() {
    devolucaoModal.classList.remove('ativo');
}

// Carregar manutenções
async function loadManutencoesTecnico() {
    listaManutencoes.innerHTML = '';
    mensagemCarregamento.classList.remove('oculto');
    hideMessage(mensagemErro);

    if (typeof userId === 'undefined' || userId === null) {
        mensagemErro.textContent = 'Erro: ID do usuário não encontrado.';
        mensagemErro.classList.remove('oculto');
        mensagemCarregamento.classList.add('oculto');
        return;
    }

    try {
        const response = await fetch(`get_manutencoes_tecnico.php?user_id=${userId}`);
        const data = await response.json();

        mensagemCarregamento.classList.add('oculto');

        if (data.success && data.manutencoes.length > 0) {
            data.manutencoes.forEach(manutencao => {
                const itemDiv = document.createElement('div');
                itemDiv.classList.add('item-manutencao');

                let textoDataExecucao = 'Data não definida';
                let diasParaReparo = 0;
                
                if (manutencao.inicio_reparoTec && manutencao.fim_reparoT) {
                    const inicioFormatado = new Date(manutencao.inicio_reparoTec).toLocaleDateString('pt-BR');
                    const fimFormatado = new Date(manutencao.fim_reparoT).toLocaleDateString('pt-BR');
                    diasParaReparo = calcularDiasEntreDatas(manutencao.inicio_reparoTec, manutencao.fim_reparoT);
                    textoDataExecucao = `<span class="periodo-data">${inicioFormatado} até ${fimFormatado}</span>`;
                }
                let textoDias = diasParaReparo === 1 ? '1 dia' : `${diasParaReparo} dias`;

                let htmlVeiculos = 'Nenhum veículo atribuído';
                if (manutencao.veiculos_info) {
                    const veiculos = manutencao.veiculos_info.split(' | ');
                    const spansVeiculos = veiculos.map(veiculo => `<span class="item-veiculo-modal">${veiculo.trim()}</span>`).join(' ');
                    htmlVeiculos = spansVeiculos;
                }

                itemDiv.innerHTML = `
                    <div>
                        <h3 class="titulo-equipamento">${manutencao.nome_equip} - ${manutencao.referencia_equip}</h3>
                        <p class="linha-info"><span class="rotulo-info"></span> ${manutencao.cidade_nome}</p>
                        <div class="descricao-problema"><span class="rotulo-info">Descrição do problema:</span> ${manutencao.ocorrencia_reparo || 'Não informada'}</div>
                        <p class="linha-data-execucao">${textoDataExecucao} <span class="total-dias">(${textoDias})</span></p>
                        <p class="linha-info"><span class="rotulo-info">Endereço:</span> ${manutencao.logradouro || 'Não informado'}</p>
                        <div class="lista-veiculos"><span class="rotulo-info">Veículo(s):</span> ${htmlVeiculos}</div>
                    </div>
                `;

                const divBotoes = document.createElement('div');
                divBotoes.classList.add('botoes-item');

                if (manutencao.latitude && manutencao.longitude) {
                    const botaoLocalizar = document.createElement('button');
                    botaoLocalizar.classList.add('botao-localizar');
                    botaoLocalizar.textContent = 'Localizar no Mapa';
                    botaoLocalizar.addEventListener('click', () => {
                        window.open(`https://www.google.com/maps/search/?api=1&query=${manutencao.latitude},${manutencao.longitude}`, '_blank');
                    });
                    divBotoes.appendChild(botaoLocalizar);
                }

                const botaoConcluir = document.createElement('button');
                botaoConcluir.classList.add('botao-concluir');
                botaoConcluir.textContent = 'Concluir Reparo';
                botaoConcluir.addEventListener('click', () => openConcluirReparoModal(manutencao));
                divBotoes.appendChild(botaoConcluir);

                const botaoDevolver = document.createElement('button');
                botaoDevolver.classList.add('botao-devolver');
                botaoDevolver.textContent = 'Devolver Reparo';
                botaoDevolver.addEventListener('click', () => openDevolucaoModal(manutencao));
                divBotoes.appendChild(botaoDevolver);

                itemDiv.appendChild(divBotoes);
                listaManutencoes.appendChild(itemDiv);
            });
        } else {
            mensagemErro.textContent = 'Nenhuma manutenção em andamento atribuída a você.';
            mensagemErro.classList.remove('oculto');
        }
    } catch (error) {
        console.error('Erro ao carregar manutenções do técnico:', error);
        mensagemCarregamento.classList.add('oculto');
        mensagemErro.textContent = 'Ocorreu um erro ao carregar suas manutenções. Tente novamente.';
        mensagemErro.classList.remove('oculto');
    }
}

// Lógica para os botões de rompimento de lacre
botaoSimRompimento.addEventListener('click', () => {
    botaoSimRompimento.classList.add('ativo');
    botaoNaoRompimento.classList.remove('ativo');
    camposRompimentoLacre.classList.remove('oculto');
});

botaoNaoRompimento.addEventListener('click', () => {
    botaoNaoRompimento.classList.add('ativo');
    botaoSimRompimento.classList.remove('ativo');
    camposRompimentoLacre.classList.add('oculto');
    // Limpa os campos quando "Não" é selecionado para evitar envio de dados incorretos
    inputNumeroLacre.value = '';
    inputInfoRompimento.value = '';
    inputDataRompimento.value = '';
});


// Atualizar status
async function atualizarStatusManutencao(idManutencao, novoStatus, descricaoReparo = null, materiaisUtilizados = null, motivoDevolucao = null, rompimentoLacre = null, numeroLacre = null, infoRompimento = null, dataRompimento = null) {
    const isDevolucao = novoStatus === 'pendente';
    const msgElemento = isDevolucao ? mensagemDevolucao : concluirReparoMessage;
    const spinner = isDevolucao ? spinnerDevolucao : concluirReparoSpinner;
    const botao = isDevolucao ? botaoConfirmarDevolucao : confirmConcluirReparoBtn;

    hideMessage(msgElemento);
    toggleSpinner(botao, spinner, true);

    const body = {
        id_manutencao: idManutencao,
        status_reparo: novoStatus,
        reparo_finalizado: descricaoReparo,
        materiais_utilizados: materiaisUtilizados,
        motivo_devolucao: motivoDevolucao,
        // Novos campos de rompimento de lacre
        rompimento_lacre: rompimentoLacre,
        numero_lacre: numeroLacre,
        info_rompimento: infoRompimento,
        data_rompimento: dataRompimento
    };

    try {
        const response = await fetch('update_manutencao_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await response.json();

        if (data.success) {
            showMessage(msgElemento, `Status atualizado com sucesso!`, 'sucesso');
            setTimeout(() => {
                isDevolucao ? closeDevolucaoModal() : closeConcluirReparoModal();
                loadManutencoesTecnico();
            }, 1500);
        } else {
            showMessage(msgElemento, `Erro ao atualizar status: ${data.message}`, 'erro');
        }
    } catch (error) {
        console.error('Erro ao atualizar status da manutenção:', error);
        showMessage(msgElemento, 'Ocorreu um erro ao tentar atualizar o status da manutenção.', 'erro');
    } finally {
        toggleSpinner(botao, spinner, false);
    }
}

// Event listener para a checkbox "Nenhum material"
checkboxNenhumMaterial.addEventListener('change', () => {
    if (checkboxNenhumMaterial.checked) {
        materiaisUtilizadosInput.value = 'Nenhum material utilizado';
        materiaisUtilizadosInput.disabled = true;
    } else {
        materiaisUtilizadosInput.value = '';
        materiaisUtilizadosInput.disabled = false;
    }
});

// Event listener para o botão de confirmação do reparo
confirmConcluirReparoBtn.addEventListener('click', () => {
    const reparoRealizado = reparoRealizadoTextarea.value.trim();
    let materiaisUtilizados = materiaisUtilizadosInput.value.trim();

    // Verificação dos campos de materiais
    if (checkboxNenhumMaterial.checked) {
        materiaisUtilizados = 'Nenhum material utilizado';
    } else if (materiaisUtilizados === '') {
        showMessage(concluirReparoMessage, 'Por favor, informe os materiais utilizados ou marque "Nenhum material".', 'erro');
        return;
    }

    if (reparoRealizado === '') {
        showMessage(concluirReparoMessage, 'Por favor, descreva o reparo realizado.', 'erro');
        return;
    }

    // Coleta e validação dos campos de rompimento de lacre
    const rompimentoLacre = botaoSimRompimento.classList.contains('ativo');
    let numeroLacre = null;
    let infoRompimento = null;
    let dataRompimento = null;
    
    if (rompimentoLacre) {
        numeroLacre = inputNumeroLacre.value.trim();
        infoRompimento = inputInfoRompimento.value.trim();
        dataRompimento = inputDataRompimento.value;

        if (!numeroLacre || !infoRompimento || !dataRompimento) {
            showMessage(concluirReparoMessage, 'Por favor, preencha todos os campos do rompimento de lacre.', 'erro');
            return;
        }
    }

    if (currentManutencaoId) {
        atualizarStatusManutencao(
            currentManutencaoId,
            'concluido',
            reparoRealizado,
            materiaisUtilizados,
            null, // motivoDevolucao
            rompimentoLacre ? 1 : 0,
            numeroLacre,
            infoRompimento,
            dataRompimento
        );
    } else {
        showMessage(concluirReparoMessage, 'Erro: ID da manutenção não encontrado.', 'erro');
    }
});


// Event listener para o botão de confirmação da devolução
botaoConfirmarDevolucao.addEventListener('click', () => {
    const motivoDevolucao = textareaDevolucao.value.trim();
    if (motivoDevolucao === '') {
        showMessage(mensagemDevolucao, 'Por favor, descreva o motivo da devolução.', 'erro');
        return;
    }
    if (currentManutencaoId) {
        atualizarStatusManutencao(currentManutencaoId, 'pendente', null, null, motivoDevolucao);
    } else {
        showMessage(mensagemDevolucao, 'Erro: ID da manutenção não encontrado.', 'erro');
    }
});

// Fecha os modais se o usuário clicar fora deles
window.onclick = function(event) {
    if (event.target === concluirReparoModal) {
        closeConcluirReparoModal();
    }
    if (event.target === devolucaoModal) {
        closeDevolucaoModal();
    }
}

// Carrega as manutenções quando a página é carregada
document.addEventListener('DOMContentLoaded', loadManutencoesTecnico);