// Funções de utilidade
function showMessage(element, msg, type) {
    if (element) {
        element.textContent = msg;
        element.className = `mensagem ${type}`;
        element.classList.remove('oculto');
    }
}

function hideMessage(element) {
    if (element) {
        element.classList.add('oculto');
        element.textContent = '';
    }
}

function toggleSpinner(button, spinner, show) {
    if (button && spinner) {
        if (show) {
            spinner.classList.remove('oculto');
            button.disabled = true;
        } else {
            spinner.classList.add('oculto');
            button.disabled = false;
        }
    }
}

function calcularDiasEntreDatas(inicio, fim) {
    if (!inicio || !fim) return 0;
    const dataInicio = new Date(inicio);
    dataInicio.setHours(0, 0, 0, 0);
    const dataFim = new Date(fim);
    dataFim.setHours(0, 0, 0, 0);
    const diferencaTempo = dataFim.getTime() - dataInicio.getTime();
    const umDiaEmMilissegundos = 1000 * 60 * 60 * 24;
    const diferencaDias = Math.round(diferencaTempo / umDiaEmMilissegundos);
    return diferencaDias + 1;
}

// Funções globais para fechar modais
function fecharModalConcluirReparo() {
    document.getElementById('concluirReparoModal')?.classList.remove('ativo');
}

function fecharModalDevolucao() {
    document.getElementById('devolucaoModal')?.classList.remove('ativo');
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Referências para elementos da DOM ---
    const listaManutencoes = document.getElementById('listaManutencoes');
    const mensagemCarregamento = document.getElementById('mensagemCarregamento');
    const mensagemErro = document.getElementById('mensagemErro');
    const btnCorretiva = document.getElementById('btnCorretiva');
    const btnInstalacao = document.getElementById('btnInstalacao');

    // Modal de Conclusão (Geral)
    const concluirReparoModal = document.getElementById('concluirReparoModal');
    const modalConcluirTitulo = document.getElementById('modalConcluirTitulo');
    const nomeEquipamentoModal = document.getElementById('nomeEquipamentoModal');
    const referenciaEquipamentoModal = document.getElementById('referenciaEquipamentoModal');
    const ocorrenciaReparoModal = document.getElementById('ocorrenciaReparoModal');
    const confirmConcluirReparoBtn = document.getElementById('confirmConcluirReparoBtn');
    const concluirReparoSpinner = document.getElementById('concluirReparoSpinner');
    const concluirReparoMessage = document.getElementById('concluirReparoMessage');

    // Campos de Reparo (Corretiva)
    const camposReparo = document.getElementById('camposReparo');
    const materiaisUtilizadosInput = document.getElementById('materiaisUtilizadosInput');
    const checkboxNenhumMaterial = document.getElementById('checkboxNenhumMaterial');
    const botaoSimRompimento = document.getElementById('botaoSimRompimento');
    const botaoNaoRompimento = document.getElementById('botaoNaoRompimento');
    const camposRompimentoLacre = document.getElementById('camposRompimentoLacre');
    const selectLacreRompido = document.getElementById('selectLacreRompido');
    const inputNumeroLacre = document.getElementById('inputNumeroLacre');
    const inputDataRompimento = document.getElementById('inputDataRompimento');
    const reparoRealizadoTextarea = document.getElementById('reparoRealizadoTextarea');

    // Campos de Instalação
    const camposInstalacao = document.getElementById('camposInstalacao');
    const dataBaseInput = document.getElementById('dataBase');
    const dataLacoInput = document.getElementById('dataLaco');
    const dataInfraInput = document.getElementById('dataInfra');
    const dataEnergiaInput = document.getElementById('dataEnergia');
    const lacoChecklistItem = document.querySelector('#dataLaco').closest('.item-checklist');

    // Modal de Devolução
    const devolucaoModal = document.getElementById('devolucaoModal');
    const nomeEquipamentoDevolucaoModal = document.getElementById('nomeEquipamentoDevolucaoModal');
    const referenciaEquipamentoDevolucaoModal = document.getElementById('referenciaEquipamentoDevolucaoModal');
    const ocorrenciaReparoDevolucaoModal = document.getElementById('ocorrenciaReparoDevolucaoModal');
    const textareaDevolucao = document.getElementById('textareaDevolucao');
    const botaoConfirmarDevolucao = document.getElementById('botaoConfirmarDevolucao');
    const spinnerDevolucao = document.getElementById('spinnerDevolucao');
    const mensagemDevolucao = document.getElementById('mensagemDevolucao');

    // Modal de Confirmação Parcial
    const partialConfirmModal = document.getElementById('partialConfirmModal');
    const listaItensConcluidos = document.getElementById('listaItensConcluidos');
    const btnConfirmarParcial = document.getElementById('btnConfirmarParcial');
    const btnCancelarParcial = document.getElementById('btnCancelarParcial');

    const btnSalvarProgresso = document.getElementById('btnSalvarProgresso');
    const salvarProgressoSpinner = document.getElementById('salvarProgressoSpinner');

    const fullConfirmModal = document.getElementById('fullConfirmModal');
    const btnConfirmarTotal = document.getElementById('btnConfirmarTotal');
    const btnCancelarTotal = document.getElementById('btnCancelarTotal')

    // --- Variáveis de Estado ---
    let currentManutencao = null;
    let filtroAtual = 'corretiva';
    let todasAsManutencoes = [];

    // --- VARIÁVEIS PARA AUTO-UPDATE ---
    let currentChecksum = null;
    let updateTimeoutId = null;
    const BASE_INTERVAL = 15000; // 15 segundos
    const MAX_INTERVAL = 120000; // 2 minutos
    let currentInterval = BASE_INTERVAL;

    // --- LÓGICA DE AUTO-UPDATE COM BACKOFF EXPONENCIAL  ---
    async function scheduleNextCheck() {
        if (updateTimeoutId) {
            clearTimeout(updateTimeoutId);
        }
        try {
            const checkResponse = await fetch(`API/check_updates.php?context=manutencao_tecnico`);
            const checkResult = await checkResponse.json();

            if (checkResult.success && checkResult.checksum !== currentChecksum) {
                console.log('Novas atualizações de manutenções detectadas. Recarregando...');
                await initialLoad(true); // Recarrega os dados sem piscar a tela
                currentInterval = BASE_INTERVAL;
                console.log('Intervalo de verificação de manutenções resetado.');
            } else {
                currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
                console.log(`Nenhuma atualização. Próxima verificação de manutenções em ${currentInterval / 1000}s.`);
            }
        } catch (error) {
            console.error('Erro no ciclo de verificação de atualizações:', error);
            currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
        } finally {
            updateTimeoutId = setTimeout(scheduleNextCheck, currentInterval);
        }
    }

    // --- Funções Principais ---

    function abrirModalConcluirReparo(manutencao) {
        currentManutencao = manutencao;
        const isInstalacao = manutencao.tipo_manutencao.toLowerCase() === 'instalação';

        modalConcluirTitulo.textContent = isInstalacao ? 'Registrar Progresso da Instalação' : 'Concluir Reparo';
        confirmConcluirReparoBtn.querySelector('span').textContent = isInstalacao ? 'Concluir Instalação' : 'Confirmar Reparo';
        confirmConcluirReparoBtn.classList.remove('oculto');

        camposReparo.classList.toggle('oculto', isInstalacao);
        camposInstalacao.classList.toggle('oculto', !isInstalacao);

        nomeEquipamentoModal.textContent = manutencao.nome_equip;
        referenciaEquipamentoModal.textContent = manutencao.referencia_equip;

        if (isInstalacao) {
            const tipoEquip = manutencao.tipo_equip || '';

            const checklistItems = {
                laco: document.querySelector('#dataLaco').closest('.item-checklist'),
                base: document.querySelector('#dataBase').closest('.item-checklist'),
                infra: document.querySelector('#dataInfra').closest('.item-checklist'),
                energia: document.querySelector('#dataEnergia').closest('.item-checklist')
            };

            // Reseta a visibilidade e preenche os dados
            Object.values(checklistItems).forEach(item => item.classList.remove('oculto'));
            dataLacoInput.value = manutencao.dt_laco || '';
            dataBaseInput.value = manutencao.dt_base || '';
            dataInfraInput.value = manutencao.data_infra || '';
            dataEnergiaInput.value = manutencao.dt_energia || '';

            // Define os passos necessários
            let passosNecessarios = ['base', 'infra', 'energia', 'laco'];
            if (tipoEquip.includes('CCO')) {
                passosNecessarios = ['infra', 'energia'];
                checklistItems.laco.classList.add('oculto');
                checklistItems.base.classList.add('oculto');
            } else if (tipoEquip.includes('DOME') || tipoEquip.includes('VÍDEO MONITORAMENTO') || tipoEquip.includes('LAP')) {
                passosNecessarios = ['base', 'infra', 'energia'];
                checklistItems.laco.classList.add('oculto');
            }

            // Verifica quantos passos faltam
            let passosFaltantes = 0;
            passosNecessarios.forEach(passo => {
                const input = document.getElementById(`data${passo.charAt(0).toUpperCase() + passo.slice(1)}`);
                if (!input.value) {
                    passosFaltantes++;
                }
            });

            // Mostra ou esconde o botão "Salvar Progresso"
            btnSalvarProgresso.classList.toggle('oculto', passosFaltantes <= 1);

        } else { // Lógica para Corretiva
            btnSalvarProgresso.classList.add('oculto');
            ocorrenciaReparoModal.textContent = manutencao.ocorrencia_reparo || 'N/A';
            reparoRealizadoTextarea.value = '';
            materiaisUtilizadosInput.value = '';
            materiaisUtilizadosInput.disabled = false;
            checkboxNenhumMaterial.checked = false;
            botaoNaoRompimento.click();
        }

        hideMessage(concluirReparoMessage);
        concluirReparoModal.classList.add('ativo');
        toggleSpinner(confirmConcluirReparoBtn, concluirReparoSpinner, false);
        toggleSpinner(btnSalvarProgresso, salvarProgressoSpinner, false);
    }

    function abrirModalDevolucao(manutencao) {
        currentManutencao = manutencao;
        if (textareaDevolucao) textareaDevolucao.value = '';
        hideMessage(mensagemDevolucao);
        if (nomeEquipamentoDevolucaoModal) nomeEquipamentoDevolucaoModal.textContent = manutencao.nome_equip;
        if (referenciaEquipamentoDevolucaoModal) referenciaEquipamentoDevolucaoModal.textContent = manutencao.referencia_equip;
        if (ocorrenciaReparoDevolucaoModal) ocorrenciaReparoDevolucaoModal.textContent = manutencao.ocorrencia_reparo || 'N/A';
        if (botaoConfirmarDevolucao) botaoConfirmarDevolucao.classList.remove('oculto');

        const isInstalacao = manutencao.tipo_manutencao.toLowerCase() === 'instalação';
        const tituloModal = devolucaoModal.querySelector('h3');
        if (tituloModal) {
            tituloModal.textContent = isInstalacao ? 'Devolver Instalação' : 'Devolução de Manutenção';
        }

        if (devolucaoModal) devolucaoModal.classList.add('ativo');
        toggleSpinner(botaoConfirmarDevolucao, spinnerDevolucao, false);
    }

    function renderManutencoes() {
        if (!listaManutencoes) return;

        listaManutencoes.innerHTML = '';
        hideMessage(mensagemErro);

        if (todasAsManutencoes.length === 0) {
            showMessage(mensagemErro, 'Nenhuma manutenção em andamento atribuída a você.', 'info');
            return;
        }

        const manutencoesFiltradas = todasAsManutencoes.filter(m => {
            const tipo = m.tipo_manutencao.toLowerCase();
            if (filtroAtual === 'instalação') {
                return tipo === 'instalação';
            } else {
                return tipo !== 'instalação';
            }
        });

        if (manutencoesFiltradas.length > 0) {
            manutencoesFiltradas.forEach(manutencao => {
                const itemDiv = document.createElement('div');
                itemDiv.classList.add('item-manutencao');

                if (manutencao.fim_reparoT) {
                    const hoje = new Date();
                    hoje.setHours(0, 0, 0, 0);
                    const dataFim = new Date(manutencao.fim_reparoT);
                    dataFim.setHours(0, 0, 0, 0);
                    if (hoje.getTime() === dataFim.getTime()) {
                        itemDiv.classList.add('vencendo-hoje');
                    } else if (hoje > dataFim) {
                        itemDiv.classList.add('vencido');
                    }
                }

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
                    htmlVeiculos = manutencao.veiculos_info.split(' | ').map(v => `<span class="item-veiculo-modal">${v.trim()}</span>`).join(' ');
                }

                let htmlConteudoPrincipal = '';
                const isInstalacao = manutencao.tipo_manutencao.toLowerCase() === 'instalação';

                if (isInstalacao) {
                    // 1. Obter o tipo do equipamento para as regras
                    const tipoEquip = manutencao.tipo_equip || 'Não especificado';

                    // 2. Criar o HTML para exibir o tipo do equipamento, acima da observação
                    const htmlTipoEquip = `<div class="descricao-problema" style="margin-top: 5px;"><span class="rotulo-info">Tipo de Equipamento:</span> ${tipoEquip}</div>`;

                    // 3. Lógica para definir os passos de instalação que serão mostrados
                    let statusMap = {
                        inst_laco: 'Laço',
                        inst_base: 'Base',
                        inst_infra: 'Infra',
                        inst_energia: 'Energia'
                    };

                    if (tipoEquip.includes('CCO')) {
                        delete statusMap.inst_laco;
                        delete statusMap.inst_base;
                    } else if (tipoEquip.includes('DOME') || tipoEquip.includes('VÍDEO MONITORAMENTO') || tipoEquip.includes('LAP')) {
                        delete statusMap.inst_laco;
                    }

                    // 4. Gerar o HTML apenas para os passos visíveis
                    const stepsHTML = Object.keys(statusMap).map(key => {
                        const statusKey = key; // ex: 'inst_laco'
                        const label = statusMap[key]; // ex: 'Laço'
                        const status = manutencao[statusKey] == 1 ? 'Instalado' : 'Aguardando Instalação';
                        return `<p class="status-item"><span class="rotulo-info">${label}:</span> ${status}</p>`;
                    }).join('');

                    // 5. Montar o HTML da observação (se existir)
                    let htmlObservacao = '';
                    if (manutencao.observacao_instalacao) {
                        htmlObservacao = `<div class="descricao-problema" style="margin-top: 5px;"><span class="rotulo-info">Observação:</span> ${manutencao.observacao_instalacao}</div>`;
                    }

                    // 6. Juntar todas as partes para formar o conteúdo principal do card
                    htmlConteudoPrincipal = `
                        ${htmlTipoEquip}
                        <div class="instalacao-status-container">${stepsHTML}</div>
                        ${htmlObservacao}
                    `;
                } else {
                    htmlConteudoPrincipal = `<div class="descricao-problema"><span class="rotulo-info">Descrição do problema:</span> ${manutencao.ocorrencia_reparo || 'Não informada'}</div>`;
                }

                itemDiv.innerHTML = `<div>
                    <h3 class="titulo-equipamento">${manutencao.nome_equip} - ${manutencao.referencia_equip}</h3>
                    <p class="linha-info">${manutencao.cidade_nome}</p>
                    ${htmlConteudoPrincipal} 
                    <p class="linha-data-execucao">${textoDataExecucao} <span class="total-dias">(${textoDias})</span></p>
                    <p class="linha-info"><span class="rotulo-info">Endereço:</span> ${manutencao.logradouro || 'Não informado'}</p>
                    <div class="lista-veiculos"><span class="rotulo-info">Veículo(s):</span> ${htmlVeiculos}</div>
                </div>`;

                const divBotoes = document.createElement('div');
                divBotoes.classList.add('botoes-item');
                if (manutencao.latitude && manutencao.longitude) {
                    const botaoLocalizar = document.createElement('button');
                    botaoLocalizar.classList.add('botao-localizar');
                    botaoLocalizar.textContent = 'Localizar no Mapa';
                    botaoLocalizar.addEventListener('click', () => {
                        window.open(`https://www.google.com/maps?q=${manutencao.latitude},${manutencao.longitude}`, '_blank');
                    });
                    divBotoes.appendChild(botaoLocalizar);
                }

                const textoConcluir = isInstalacao ? 'Registrar Progresso' : 'Concluir Reparo';
                const textoDevolver = isInstalacao ? 'Devolver Instalação' : 'Devolver Reparo';

                const botaoConcluir = document.createElement('button');
                botaoConcluir.classList.add('botao-concluir');
                botaoConcluir.textContent = textoConcluir;
                botaoConcluir.addEventListener('click', () => abrirModalConcluirReparo(manutencao));
                divBotoes.appendChild(botaoConcluir);

                const botaoDevolver = document.createElement('button');
                botaoDevolver.classList.add('botao-devolver');
                botaoDevolver.textContent = textoDevolver;
                botaoDevolver.addEventListener('click', () => abrirModalDevolucao(manutencao));
                divBotoes.appendChild(botaoDevolver);

                itemDiv.appendChild(divBotoes);
                listaManutencoes.appendChild(itemDiv);
            });
        } else {
            const tipo = filtroAtual === 'instalação' ? 'instalações' : 'manutenções';
            showMessage(mensagemErro, `Nenhuma ${tipo} encontrada.`, 'info');
        }
    }

    async function atualizarStatusManutencao(payload) {
        const isDevolucao = payload.motivo_devolucao != null;
        const msgElemento = isDevolucao ? mensagemDevolucao : concluirReparoMessage;
        const spinner = isDevolucao ? spinnerDevolucao : concluirReparoSpinner;
        const botao = isDevolucao ? botaoConfirmarDevolucao : confirmConcluirReparoBtn;

        hideMessage(msgElemento);
        toggleSpinner(botao, spinner, true);

        try {
            const response = await fetch('API/update_manutencao_status.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (data.success) {
                spinner.classList.add('oculto');
                if (botao) botao.classList.add('oculto');
                showMessage(msgElemento, data.message || 'Status atualizado com sucesso!', 'sucesso');
                setTimeout(() => {
                    isDevolucao ? fecharModalDevolucao() : fecharModalConcluirReparo();
                    initialLoad();
                }, 2000);
            } else {
                toggleSpinner(botao, spinner, false);
                showMessage(msgElemento, `Erro: ${data.message}`, 'erro');
            }
        } catch (error) {
            console.error('Erro ao atualizar status:', error);
            toggleSpinner(botao, spinner, false);
            showMessage(msgElemento, 'Erro de comunicação ao atualizar.', 'erro');
        }
    }

    async function executarSalvamentoInstalacao(isFinal) {
        let novoStatus;

        if (!isFinal) {
            // 1. Ação: "Salvar Progresso" -> A tarefa continua com o técnico.
            novoStatus = 'em andamento';
        } else {
            // 2. Ação: "Concluir Instalação" -> O status depende se a conclusão é total ou parcial.
            const tipoEquip = currentManutencao.tipo_equip || '';

            // Determina os passos necessários para este tipo de equipamento
            let passosNecessarios = ['laco', 'base', 'infra', 'energia'];
            if (tipoEquip.includes('CCO')) {
                passosNecessarios = ['infra', 'energia'];
            } else if (tipoEquip.includes('DOME') || tipoEquip.includes('VÍDEO MONITORAMENTO') || tipoEquip.includes('LAP')) {
                passosNecessarios = ['base', 'infra', 'energia'];
            }

            // Verifica se todos os passos necessários foram preenchidos
            const allFilled = passosNecessarios.every(passo => {
                // Constrói o ID do input de data (ex: 'dataLaco', 'dataBase')
                const inputId = `data${passo.charAt(0).toUpperCase() + passo.slice(1)}`;
                const input = document.getElementById(inputId);
                return input && input.value;
            });

            if (allFilled) {
                // 2a. Conclusão TOTAL: Continua 'em andamento' para a próxima fase do processo (sem técnico).
                novoStatus = 'em andamento';
            } else {
                // 2b. Conclusão PARCIAL: Volta para a fila geral como 'pendente'.
                novoStatus = 'pendente';
            }
        }
        const payload = {
            id_manutencao: currentManutencao.id_manutencao,
            is_installation: true,
            is_final: isFinal,
            status_reparo: novoStatus,
            dt_base: dataBaseInput.value || null,
            dt_laco: dataLacoInput.value || null,
            data_infra: dataInfraInput.value || null,
            dt_energia: dataEnergiaInput.value || null,
            tipo_equip: currentManutencao.tipo_equip,
            id_cidade: currentManutencao.id_cidade
        };

        const targetButton = isFinal ? confirmConcluirReparoBtn : btnSalvarProgresso;
        const targetSpinner = isFinal ? concluirReparoSpinner : salvarProgressoSpinner;

        hideMessage(concluirReparoMessage);
        toggleSpinner(targetButton, targetSpinner, true);

        try {
            const response = await fetch('API/update_manutencao_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();

            if (data.success) {
                if (isFinal) {
                    confirmConcluirReparoBtn.classList.add('oculto');
                    btnSalvarProgresso.classList.add('oculto');
                }
                showMessage(concluirReparoMessage, data.message || 'Operação realizada com sucesso!', 'sucesso');
                setTimeout(() => {
                    fecharModalConcluirReparo();
                    initialLoad();
                }, 2000);
            } else {
                throw new Error(data.message || 'Falha ao salvar.');
            }
        } catch (error) {
            showMessage(concluirReparoMessage, error.message, 'erro');
        } finally {
            toggleSpinner(targetButton, targetSpinner, false);
        }
    }

    function handleFiltroClick(tipo) {
        filtroAtual = tipo;
        btnCorretiva.classList.toggle('ativo', tipo === 'corretiva');
        btnInstalacao.classList.toggle('ativo', tipo === 'instalação');
        renderManutencoes();
    }

    /*sync function checkForUpdates() {
        try {
            const response = await fetch(`API/get_manutencoes_tecnico.php?user_id=${userId}`);
            const newData = await response.json();

            if (newData.success && newData.manutencoes) {
                const oldSignature = todasAsManutencoes.map(m => m.id_manutencao).sort().join(',');
                const newSignature = newData.manutencoes.map(m => m.id_manutencao).sort().join(',');
                if (newSignature !== oldSignature) {
                    todasAsManutencoes = newData.manutencoes;
                    renderManutencoes();
                }
            }
        } catch (error) {
            console.error("Erro ao verificar atualizações:", error);
        }
    }*/

    // --- Carregamento Inicial ---

    async function initialLoad(isUpdate = false) {
        if (!isUpdate) { // Só mostra o "carregando" na primeira vez
            mensagemCarregamento.classList.remove('oculto');
            hideMessage(mensagemErro);
        }
        try {
            const response = await fetch(`API/get_manutencoes_tecnico.php?user_id=${userId}`);
            const data = await response.json();

            if (data.success) {
                // ATUALIZA O CHECKSUM LOCAL
                currentChecksum = data.checksum;
                todasAsManutencoes = data.manutencoes || [];

                // Se não for uma atualização em segundo plano, define o filtro inicial
                if (!isUpdate) {
                    const hasManutencoes = todasAsManutencoes.some(m => m.tipo_manutencao.toLowerCase() !== 'instalação');
                    filtroAtual = hasManutencoes ? 'corretiva' : 'instalação';
                    btnCorretiva.classList.toggle('ativo', filtroAtual === 'corretiva');
                    btnInstalacao.classList.toggle('ativo', filtroAtual === 'instalação');
                }

                renderManutencoes();
            } else {
                // Mesmo em caso de "falha" (sem manutenções), atualizamos o checksum
                currentChecksum = data.checksum;
                todasAsManutencoes = [];
                renderManutencoes();
            }

        } catch (error) {
            console.error('Erro no carregamento de dados:', error);
            if (!isUpdate) {
                todasAsManutencoes = [];
                showMessage(mensagemErro, 'Ocorreu um erro ao carregar suas manutenções.', 'erro');
            }
        } finally {
            if (!isUpdate) {
                mensagemCarregamento.classList.add('oculto');
            }
        }
    }

    // --- Event Listeners ---
    if (btnCorretiva) btnCorretiva.addEventListener('click', () => handleFiltroClick('corretiva'));
    if (btnInstalacao) btnInstalacao.addEventListener('click', () => handleFiltroClick('instalação'));

    if (checkboxNenhumMaterial) {
        checkboxNenhumMaterial.addEventListener('change', () => {
            materiaisUtilizadosInput.value = checkboxNenhumMaterial.checked ? 'Nenhum material utilizado' : '';
            materiaisUtilizadosInput.disabled = checkboxNenhumMaterial.checked;
        });
    }

    if (botaoSimRompimento) {
        botaoSimRompimento.addEventListener('click', async () => {
            botaoSimRompimento.classList.add('ativo');
            botaoNaoRompimento.classList.remove('ativo');
            camposRompimentoLacre.classList.remove('oculto');

            // Limpa e prepara os campos
            selectLacreRompido.innerHTML = '<option value="">Carregando lacres...</option>';
            inputNumeroLacre.value = '';
            inputDataRompimento.value = '';

            if (!currentManutencao || !currentManutencao.id_equipamento) {
                selectLacreRompido.innerHTML = '<option value="">Erro: Equipamento não identificado.</option>';
                return;
            }

            try {
                // Busca os lacres da nova API
                const response = await fetch(`API/get_lacres_por_equipamento.php?id_equipamento=${currentManutencao.id_equipamento}`);
                const data = await response.json();

                if (data.success && data.lacres.length > 0) {
                    selectLacreRompido.innerHTML = '<option value="">Selecione um lacre...</option>';
                    data.lacres.forEach(lacre => {
                        const option = document.createElement('option');
                        option.value = lacre.local_lacre;
                        option.textContent = lacre.local_lacre;
                        // Armazena o número do lacre no atributo data-* para fácil acesso
                        option.dataset.numLacre = lacre.num_lacre;
                        selectLacreRompido.appendChild(option);
                    });
                } else {
                    selectLacreRompido.innerHTML = '<option value="">Nenhum lacre afixado encontrado.</option>';
                }
            } catch (error) {
                console.error("Erro ao buscar lacres:", error);
                selectLacreRompido.innerHTML = '<option value="">Falha ao carregar lacres.</option>';
            }
        });
    }

    if (botaoNaoRompimento) {
        botaoNaoRompimento.addEventListener('click', () => {
            botaoNaoRompimento.classList.add('ativo');
            botaoSimRompimento.classList.remove('ativo');
            camposRompimentoLacre.classList.add('oculto');
            if (inputNumeroLacre) inputNumeroLacre.value = '';
            if (inputInfoRompimento) inputInfoRompimento.value = '';
        });
    }

    // listener para preencher o número do lacre automaticamente
    if (selectLacreRompido) {
        selectLacreRompido.addEventListener('change', () => {
            const selectedOption = selectLacreRompido.options[selectLacreRompido.selectedIndex];
            if (selectedOption && selectedOption.dataset.numLacre) {
                inputNumeroLacre.value = selectedOption.dataset.numLacre;
            } else {
                inputNumeroLacre.value = '';
            }
        });
    }

    if (confirmConcluirReparoBtn) {
        confirmConcluirReparoBtn.addEventListener('click', () => {
            if (!currentManutencao) return;
            const isInstalacao = currentManutencao.tipo_manutencao.toLowerCase() === 'instalação';

            if (isInstalacao) {
                // Verifica quais campos estão visíveis
                const visibleChecklistItems = Array.from(camposInstalacao.querySelectorAll('.item-checklist:not(.oculto)'));
                const visibleInputs = visibleChecklistItems.map(item => item.querySelector('input[type="date"]'));

                // Verifica se todos os campos visíveis estão preenchidos
                const allFilled = visibleInputs.every(input => input.value);

                if (allFilled) {
                    // Se tudo está preenchido, abre o modal de conclusão total
                    fullConfirmModal.classList.add('ativo');
                } else {
                    // Se algo falta, abre o modal de conclusão parcial
                    const preenchidos = visibleInputs.filter(input => input.value).map(input => input.closest('.item-checklist').querySelector('label b').textContent);
                    if (preenchidos.length === 0) {
                        showMessage(concluirReparoMessage, 'Preencha pelo menos uma data para concluir.', 'erro');
                        return;
                    }
                    listaItensConcluidos.innerHTML = preenchidos.map(item => `<li>${item}</li>`).join('');
                    partialConfirmModal.classList.add('ativo');
                }
            } else { // Lógica para Corretiva
                const reparoRealizado = reparoRealizadoTextarea.value.trim();
                let materiaisUtilizados = materiaisUtilizadosInput.value.trim();
                if (checkboxNenhumMaterial.checked) {
                    materiaisUtilizados = 'Nenhum material utilizado';
                } else if (materiaisUtilizados === '') {
                    showMessage(concluirReparoMessage, 'Informe os materiais ou marque "Nenhum material".', 'erro');
                    return;
                }
                if (reparoRealizado === '') {
                    showMessage(concluirReparoMessage, 'Descreva o reparo realizado.', 'erro');
                    return;
                }
                const rompimentoLacre = botaoSimRompimento.classList.contains('ativo');
                let numeroLacre = null, infoRompimento = null, dataRompimento = null;

                if (rompimentoLacre) {
                    infoRompimento = selectLacreRompido.value; // O local do lacre
                    numeroLacre = inputNumeroLacre.value;     // O número do lacre
                    dataRompimento = inputDataRompimento.value; // A data

                    if (!infoRompimento) {
                        showMessage(concluirReparoMessage, 'Selecione o lacre que foi rompido.', 'erro');
                        return;
                    }
                    if (!dataRompimento) {
                        showMessage(concluirReparoMessage, 'A data do rompimento é obrigatória.', 'erro');
                        return;
                    }
                }

                const payload = {
                    id_manutencao: currentManutencao.id_manutencao,
                    id_equipamento: currentManutencao.id_equipamento, // Enviando o ID do equipamento
                    is_installation: false,
                    status_reparo: 'validacao',
                    reparo_finalizado: reparoRealizado,
                    materiais_utilizados: materiaisUtilizados,
                    rompimento_lacre: rompimentoLacre ? 1 : 0,
                    numero_lacre: numeroLacre,
                    info_rompimento: infoRompimento,
                    data_rompimento: dataRompimento
                };
                atualizarStatusManutencao(payload);
            }
        });
    }

    if (botaoConfirmarDevolucao) {
        botaoConfirmarDevolucao.addEventListener('click', () => {
            const motivoDevolucao = textareaDevolucao.value.trim();
            if (motivoDevolucao === '') {
                showMessage(mensagemDevolucao, 'Descreva o motivo da devolução.', 'erro');
                return;
            }
            const payload = {
                id_manutencao: currentManutencao.id_manutencao,
                status_reparo: 'pendente',
                motivo_devolucao: motivoDevolucao
            };
            atualizarStatusManutencao(payload);
        });
    }

    if (btnCancelarParcial) btnCancelarParcial.addEventListener('click', () => partialConfirmModal.classList.remove('ativo'));

    // Adiciona o listener para o novo botão de salvar progresso
    if (btnSalvarProgresso) {
        btnSalvarProgresso.addEventListener('click', () => {
            // Chama a função de salvamento com 'isFinal' como false
            executarSalvamentoInstalacao(false);
        });
    }

    // Listeners para o novo modal de confirmação total
    if (btnConfirmarTotal) {
        btnConfirmarTotal.addEventListener('click', () => {
            fullConfirmModal.classList.remove('ativo');
            executarSalvamentoInstalacao(true); // Chama a função de salvamento para concluir
        });
    }
    if (btnCancelarTotal) {
        btnCancelarTotal.addEventListener('click', () => fullConfirmModal.classList.remove('ativo'));
    }

    // Listener para o modal de confirmação parcial (atualizado)
    if (btnConfirmarParcial) {
        btnConfirmarParcial.addEventListener('click', () => {
            partialConfirmModal.classList.remove('ativo');
            executarSalvamentoInstalacao(true); // Chama a função para concluir parcialmente
        });
    }

    // Listeners para apagar a mensagem de erro ao digitar
    [reparoRealizadoTextarea, dataBaseInput, dataLacoInput, dataInfraInput, dataEnergiaInput].forEach(input => {
        if (input) {
            input.addEventListener('input', () => hideMessage(concluirReparoMessage));
        }
    });

    window.onclick = function (event) {
        if (event.target === concluirReparoModal) fecharModalConcluirReparo();
        if (event.target === devolucaoModal) fecharModalDevolucao();
        if (event.target === partialConfirmModal) partialConfirmModal.classList.remove('ativo');
    }

    // --- Carga Inicial ---
    initialLoad().then(() => {
        console.log('Carga inicial de manutenções do técnico completa. Iniciando ciclo de verificação.');
        scheduleNextCheck();
    });
});