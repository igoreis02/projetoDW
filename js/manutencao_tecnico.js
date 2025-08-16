// Funções de utilidade (podem ser globais)
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
    if (!inicio || !fim) {
        return 0;
    }

    const dataInicio = new Date(inicio);
    dataInicio.setHours(0, 0, 0, 0);

    const dataFim = new Date(fim);
    dataFim.setHours(0, 0, 0, 0);

    const diferencaTempo = dataFim.getTime() - dataInicio.getTime();
    const umDiaEmMilissegundos = 1000 * 60 * 60 * 24;
    const diferencaDias = Math.round(diferencaTempo / umDiaEmMilissegundos);

    return diferencaDias + 1;
}

// Funções globais para abrir e fechar modais, acessíveis pelo 'onclick' do HTML
function fecharModalConcluirReparo() {
    const concluirReparoModal = document.getElementById('concluirReparoModal');
    if (concluirReparoModal) {
        concluirReparoModal.classList.remove('ativo');
    }
}

function fecharModalDevolucao() {
    const devolucaoModal = document.getElementById('devolucaoModal');
    if (devolucaoModal) {
        devolucaoModal.classList.remove('ativo');
    }
}

// O código principal agora está dentro do DOMContentLoaded para garantir que o DOM esteja carregado
document.addEventListener('DOMContentLoaded', () => {

    // Referências para elementos da DOM
    const listaManutencoes = document.getElementById('listaManutencoes');
    const mensagemCarregamento = document.getElementById('mensagemCarregamento');
    const mensagemErro = document.getElementById('mensagemErro');
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
    const botaoSimRompimento = document.getElementById('botaoSimRompimento');
    const botaoNaoRompimento = document.getElementById('botaoNaoRompimento');
    const camposRompimentoLacre = document.getElementById('camposRompimentoLacre');
    const inputNumeroLacre = document.getElementById('inputNumeroLacre');
    const inputInfoRompimento = document.getElementById('inputInfoRompimento');
    const devolucaoModal = document.getElementById('devolucaoModal');
    const nomeEquipamentoDevolucaoModal = document.getElementById('nomeEquipamentoDevolucaoModal');
    const referenciaEquipamentoDevolucaoModal = document.getElementById('referenciaEquipamentoDevolucaoModal');
    const ocorrenciaReparoDevolucaoModal = document.getElementById('ocorrenciaReparoDevolucaoModal');
    const textareaDevolucao = document.getElementById('textareaDevolucao');
    const botaoConfirmarDevolucao = document.getElementById('botaoConfirmarDevolucao');
    const spinnerDevolucao = document.getElementById('spinnerDevolucao');
    const mensagemDevolucao = document.getElementById('mensagemDevolucao');

    let currentManutencaoId = null;

    const btnCorretiva = document.getElementById('btnCorretiva');
    const btnInstalacao = document.getElementById('btnInstalacao');
    let filtroAtual = 'corretiva';

    // === FUNÇÃO MODIFICADA ===
    // Modificada para também alterar o TÍTULO do modal
    function abrirModalConcluirReparo(manutencao) {
        currentManutencaoId = manutencao.id_manutencao;
        reparoRealizadoTextarea.value = '';
        materiaisUtilizadosInput.value = '';
        materiaisUtilizadosInput.disabled = false;
        checkboxNenhumMaterial.checked = false;
        hideMessage(concluirReparoMessage);
        botaoNaoRompimento.classList.add('ativo');
        botaoSimRompimento.classList.remove('ativo');
        camposRompimentoLacre.classList.add('oculto');
        inputNumeroLacre.value = '';
        inputInfoRompimento.value = '';
        nomeEquipamentoModal.textContent = manutencao.nome_equip;
        referenciaEquipamentoModal.textContent = manutencao.referencia_equip;
        ocorrenciaReparoModal.textContent = manutencao.ocorrencia_reparo || 'N/A';

        // Altera o título do modal dinamicamente
        const isInstalacao = manutencao.tipo_manutencao.toLowerCase() === 'instalação';
        const tituloModal = concluirReparoModal.querySelector('h3');
        if (tituloModal) {
            tituloModal.textContent = isInstalacao ? 'Concluir Instalação' : 'Concluir Reparo';
        }

        concluirReparoModal.classList.add('ativo');
        confirmConcluirReparoBtn.classList.remove('oculto');
        toggleSpinner(confirmConcluirReparoBtn, concluirReparoSpinner, false);
    }

    // === FUNÇÃO MODIFICADA ===
    // Modificada para também alterar o TÍTULO do modal
    function abrirModalDevolucao(manutencao) {
        currentManutencaoId = manutencao.id_manutencao;
        if (textareaDevolucao) textareaDevolucao.value = '';
        hideMessage(mensagemDevolucao);
        if (nomeEquipamentoDevolucaoModal) nomeEquipamentoDevolucaoModal.textContent = manutencao.nome_equip;
        if (referenciaEquipamentoDevolucaoModal) referenciaEquipamentoDevolucaoModal.textContent = manutencao.referencia_equip;
        if (ocorrenciaReparoDevolucaoModal) ocorrenciaReparoDevolucaoModal.textContent = manutencao.ocorrencia_reparo || 'N/A';
        
        // Altera o título do modal dinamicamente
        const isInstalacao = manutencao.tipo_manutencao.toLowerCase() === 'instalação';
        const tituloModal = devolucaoModal.querySelector('h3');
        if (tituloModal) {
            tituloModal.textContent = isInstalacao ? 'Devolver Instalação' : 'Devolução de Manutenção';
        }
        
        if (devolucaoModal) devolucaoModal.classList.add('ativo');
        toggleSpinner(botaoConfirmarDevolucao, spinnerDevolucao, false);
    }

    // === FUNÇÃO MODIFICADA ===
    // Modificada para criar os botões com textos dinâmicos
    async function loadManutencoesTecnico() {
        if (!listaManutencoes || !mensagemCarregamento || !mensagemErro || typeof userId === 'undefined' || userId === null) {
            showMessage(mensagemErro, 'Erro de inicialização.', 'erro');
            return;
        }
        listaManutencoes.innerHTML = '';
        mensagemCarregamento.classList.remove('oculto');
        hideMessage(mensagemErro);

        try {
            const response = await fetch(`get_manutencoes_tecnico.php?user_id=${userId}`);
            const data = await response.json();
            mensagemCarregamento.classList.add('oculto');

            if (data.success && data.manutencoes.length > 0) {
                const manutencoesFiltradas = data.manutencoes.filter(m => m.tipo_manutencao.toLowerCase() === filtroAtual.toLowerCase());
                
                if (manutencoesFiltradas.length > 0) {
                    manutencoesFiltradas.forEach(manutencao => {
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
                            htmlVeiculos = manutencao.veiculos_info.split(' | ').map(v => `<span class="item-veiculo-modal">${v.trim()}</span>`).join(' ');
                        }
                        
                        let htmlConteudoPrincipal = '';
                        const isInstalacao = manutencao.tipo_manutencao.toLowerCase() === 'instalação';

                        if (isInstalacao) {
                            const statusLaco = manutencao.inst_laco == 1 ? 'Instalado' : 'Aguardando Instalação';
                            const statusBase = manutencao.inst_base == 1 ? 'Instalado' : 'Aguardando Instalação';
                            const statusInfra = manutencao.inst_infra == 1 ? 'Instalado' : 'Aguardando Instalação';
                            const statusEnergia = manutencao.inst_energia == 1 ? 'Instalado' : 'Aguardando Instalação';
                            htmlConteudoPrincipal = `<div class="instalacao-status-container">
                                <p class="status-item"><span class="rotulo-info">Laço:</span> ${statusLaco}</p>
                                <p class="status-item"><span class="rotulo-info">Base:</span> ${statusBase}</p>
                                <p class="status-item"><span class="rotulo-info">Infra:</span> ${statusInfra}</p>
                                <p class="status-item"><span class="rotulo-info">Energia:</span> ${statusEnergia}</p>
                            </div>`;
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
                                window.open(`http://maps.google.com/maps?q=${manutencao.latitude},${manutencao.longitude}`, '_blank');
                            });
                            divBotoes.appendChild(botaoLocalizar);
                        }
        
                        // Define os textos dos botões dinamicamente
                        const textoConcluir = isInstalacao ? 'Concluir Instalação' : 'Concluir Reparo';
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
                     const tipo = filtroAtual === 'instalação' ? 'instalação' : 'corretiva';
                     showMessage(mensagemErro, `Nenhuma manutenção do tipo '${tipo}' encontrada.`, 'info');
                }
            } else {
                showMessage(mensagemErro, 'Nenhuma manutenção em andamento atribuída a você.', 'info');
            }
        } catch (error) {
            console.error('Erro ao carregar manutenções do técnico:', error);
            mensagemCarregamento.classList.add('oculto');
            showMessage(mensagemErro, 'Ocorreu um erro ao carregar suas manutenções.', 'erro');
        }
    }

    // O restante do seu arquivo JS permanece o mesmo...
    // ... (funções 'atualizarStatusManutencao', 'handleFiltroClick', 'initialLoad', etc.)
    
    // (Cole o resto do seu arquivo JS daqui para baixo para garantir que tudo continue funcionando)
    
    async function atualizarStatusManutencao(idManutencao, novoStatus, reparoFinalizado = null, materiaisUtilizados = null, motivoDevolucao = null, rompimentoLacre = null, numeroLacre = null, infoRompimento = null, dataRompimento = null) {
        const isDevolucao = novoStatus === 'pendente';
        const msgElemento = isDevolucao ? mensagemDevolucao : concluirReparoMessage;
        const spinner = isDevolucao ? spinnerDevolucao : concluirReparoSpinner;
        const botao = isDevolucao ? botaoConfirmarDevolucao : confirmConcluirReparoBtn;
        hideMessage(msgElemento);
        toggleSpinner(botao, spinner, true);
        const body = { id_manutencao: idManutencao, status_reparo: novoStatus, reparo_finalizado: reparoFinalizado, materiais_utilizados: materiaisUtilizados, motivo_devolucao: motivoDevolucao, rompimento_lacre: rompimentoLacre, numero_lacre: numeroLacre, info_rompimento: infoRompimento, data_rompimento: dataRompimento };
        try {
            const response = await fetch('update_manutencao_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
            const data = await response.json();
            if (data.success) {
                spinner.classList.add('oculto');
                botao.classList.add('oculto');
                showMessage(msgElemento, `Status atualizado com sucesso!`, 'sucesso');
                setTimeout(() => {
                    isDevolucao ? fecharModalDevolucao() : fecharModalConcluirReparo();
                    loadManutencoesTecnico();
                }, 1500);
            } else {
                toggleSpinner(botao, spinner, false);
                showMessage(msgElemento, `Erro ao atualizar status: ${data.message}`, 'erro');
            }
        } catch (error) {
            console.error('Erro ao atualizar status da manutenção:', error);
            toggleSpinner(botao, spinner, false);
            showMessage(msgElemento, 'Ocorreu um erro ao tentar atualizar o status da manutenção.', 'erro');
        }
    }

    if (checkboxNenhumMaterial) {
        checkboxNenhumMaterial.addEventListener('change', () => {
            materiaisUtilizadosInput.value = checkboxNenhumMaterial.checked ? 'Nenhum material utilizado' : '';
            materiaisUtilizadosInput.disabled = checkboxNenhumMaterial.checked;
        });
    }

    if (confirmConcluirReparoBtn) {
        confirmConcluirReparoBtn.addEventListener('click', () => {
            const reparoRealizado = reparoRealizadoTextarea ? reparoRealizadoTextarea.value.trim() : '';
            let materiaisUtilizados = materiaisUtilizadosInput ? materiaisUtilizadosInput.value.trim() : '';
            if (checkboxNenhumMaterial && checkboxNenhumMaterial.checked) {
                materiaisUtilizados = 'Nenhum material utilizado';
            } else if (materiaisUtilizados === '') {
                showMessage(concluirReparoMessage, 'Por favor, informe os materiais utilizados ou marque "Nenhum material".', 'erro');
                return;
            }
            if (reparoRealizado === '') {
                showMessage(concluirReparoMessage, 'Por favor, descreva o reparo realizado.', 'erro');
                return;
            }
            const rompimentoLacre = botaoSimRompimento && botaoSimRompimento.classList.contains('ativo');
            let numeroLacre = null, infoRompimento = null, dataRompimento = null;
            if (rompimentoLacre) {
                numeroLacre = inputNumeroLacre ? inputNumeroLacre.value.trim() : null;
                infoRompimento = inputInfoRompimento ? inputInfoRompimento.value.trim() : null;
                dataRompimento = new Date().toISOString().slice(0, 10);
                if (!numeroLacre || !infoRompimento) {
                    showMessage(concluirReparoMessage, 'Por favor, preencha o número do lacre e as informações de rompimento.', 'erro');
                    return;
                }
            }
            if (currentManutencaoId) {
                atualizarStatusManutencao(currentManutencaoId, 'concluido', reparoRealizado, materiaisUtilizados, null, rompimentoLacre ? 1 : 0, numeroLacre, infoRompimento, dataRompimento);
            } else {
                showMessage(concluirReparoMessage, 'Erro: ID da manutenção não encontrado.', 'erro');
            }
        });
    }
    
    function handleFiltroClick(tipo) {
        filtroAtual = tipo;
        btnCorretiva.classList.toggle('ativo', tipo === 'corretiva');
        btnInstalacao.classList.toggle('ativo', tipo === 'instalação');
        loadManutencoesTecnico();
    }

    async function initialLoad() {
        mensagemCarregamento.classList.remove('oculto');
        hideMessage(mensagemErro);
        try {
            const response = await fetch(`get_manutencoes_tecnico.php?user_id=${userId}`);
            const data = await response.json();
            if (data.success && data.manutencoes.length > 0) {
                const allItems = data.manutencoes;
                const hasCorretivas = allItems.some(m => m.tipo_manutencao.toLowerCase() === 'corretiva');
                const hasInstalacoes = allItems.some(m => m.tipo_manutencao.toLowerCase() === 'instalação');
                if (hasCorretivas) {
                    handleFiltroClick('corretiva');
                } else if (hasInstalacoes) {
                    handleFiltroClick('instalação');
                } else {
                    handleFiltroClick('corretiva');
                }
            } else {
                mensagemCarregamento.classList.add('oculto');
                showMessage(mensagemErro, 'Nenhuma manutenção em andamento atribuída a você.', 'info');
            }
        } catch (error) {
            console.error('Erro no carregamento inicial:', error);
            mensagemCarregamento.classList.add('oculto');
            showMessage(mensagemErro, 'Ocorreu um erro ao carregar suas manutenções.', 'erro');
        }
    }

    if (btnCorretiva) btnCorretiva.addEventListener('click', () => handleFiltroClick('corretiva'));
    if (btnInstalacao) btnInstalacao.addEventListener('click', () => handleFiltroClick('instalação'));

    window.onclick = function (event) {
        if (event.target === concluirReparoModal) fecharModalConcluirReparo();
        if (event.target === devolucaoModal) fecharModalDevolucao();
    }

    initialLoad();
});