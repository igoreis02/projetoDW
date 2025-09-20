document.addEventListener('DOMContentLoaded', () => {
    let todosOsEquipamentos = [];
    let lacresParaConfirmar = {};
    let operacaoAtual = '';
    let filtroCidadeAtivo = 'Todas';
    let botaoEditarPressionado = null;

    const LacreMap = {
        'metrologico': { dbValue: 'metrologico', displayName: 'Metrológico' },
        'nao_metrologico': { dbValue: 'nao metrologico', displayName: 'Não Metrológico' },
        'fonte': { dbValue: 'fonte', displayName: 'Fonte' },
        'switch_lacre': { dbValue: 'switch', displayName: 'Switch' },
        'camera_zoom_ab': { dbValue: 'camera zoom (fx. A/B)', displayName: 'Câmera Zoom (fx. A/B)' },
        'camera_zoom_a': { dbValue: 'camera zoom (fx. A)', displayName: 'Câmera Zoom (fx. A)' },
        'camera_zoom_b': { dbValue: 'camera zoom (fx. B)', displayName: 'Câmera Zoom (fx. B)' },
        'camera_pam_ab': { dbValue: 'camera pam (fx. A/B)', displayName: 'Câmera PAM (fx. A/B)' },
        'camera_pam_a': { dbValue: 'camera pam (fx. A)', displayName: 'Câmera PAM (fx. A)' },
        'camera_pam_b': { dbValue: 'camera pam (fx. B)', displayName: 'Câmera PAM (fx. B)' }
    };

    // Função chamada pelo novo botão "Lacre Rompido"
    window.abrirModalLacreRompido = async (btn) => {
        const idEquipamento = btn.dataset.equipId;
        const nomeEquipamento = btn.dataset.equipName;

        const modal = document.getElementById('modalLacreRompido');
        const form = document.getElementById('formLacreRompido');
        const containerLacres = document.getElementById('listaLacresAfixados');
        const containerDetalhes = document.getElementById('detalhesLacresSelecionados');

        // Resetar o estado do modal
        form.reset();
        containerDetalhes.innerHTML = '';
        containerDetalhes.classList.add('hidden');
        document.getElementById('mensagemErroRompimento').style.display = 'none';

        document.getElementById('tituloModalLacreRompido').textContent = `Lacre Rompido no equipamento - ${nomeEquipamento}`;
        form.querySelector('[name="id_equipamento"]').value = idEquipamento;
        containerLacres.innerHTML = '<p>Carregando lacres afixados...</p>';
        abrirModal('modalLacreRompido');

        try {

            const response = await fetch(`API/get_lacres_por_equipamento.php?id_equipamento=${idEquipamento}`);
            const data = await response.json();

            if (data.success && data.lacres.length > 0) {
                containerLacres.innerHTML = '';
                data.lacres.forEach(lacre => {
                    const div = document.createElement('div');
                    div.innerHTML = `
                    <label>
                        <input type="checkbox" name="lacres_rompidos" value="${lacre.local_lacre}|${lacre.num_lacre}">
                        <strong>${lacre.local_lacre}:</strong> ${lacre.num_lacre}
                    </label>
                `;
                    containerLacres.appendChild(div);
                });

                // Adiciona o evento para mostrar/esconder as caixas de observação
                containerLacres.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.addEventListener('change', () => toggleDetalhesLacreRompido(checkbox));
                });

            } else {
                containerLacres.innerHTML = '<p>Nenhum lacre afixado encontrado para este equipamento.</p>';
            }
        } catch (error) {
            containerLacres.innerHTML = '<p class="mensagem erro">Erro ao buscar lacres do equipamento.</p>';
        }
    };

    // Mostra a caixa de observação quando um lacre é selecionado
    function toggleDetalhesLacreRompido(checkbox) {
        const containerDetalhes = document.getElementById('detalhesLacresSelecionados');
        const [local, numero] = checkbox.value.split('|');
        const detalheId = `detalhe-${local.replace(/[^a-zA-Z0-9]/g, '')}`;

        if (checkbox.checked) {
            containerDetalhes.classList.remove('hidden');
            const div = document.createElement('div');
            div.id = detalheId;
            div.className = 'form-lacre-group';
            div.innerHTML = `
            <label>${local}</label>
            <p><strong>Número:</strong> ${numero}</p>
            <textarea name="obs_${local}" class="obs-lacre" placeholder="Observações (opcional)..."></textarea>
            <label for="data_${local}" style="margin-top: 8px;">Data do Rompimento:</label>
            <input type="date" name="data_${local}" required style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

            <label for="psie_${local}" style="margin-top: 8px;">Reporta PSIE:</label>
            <input type="date" name="psie_${local}" style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
        `;
            containerDetalhes.appendChild(div);
        } else {
            const detalheExistente = document.getElementById(detalheId);
            if (detalheExistente) detalheExistente.remove();
            if (containerDetalhes.children.length === 0) containerDetalhes.classList.add('hidden');
        }
    }

    // Prepara os dados e abre o modal de confirmação
    window.prepararConfirmacaoRompimento = (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const lacresSelecionados = formData.getAll('lacres_rompidos');
        const msgErro = document.getElementById('mensagemErroRompimento');
        let isDataValid = true;

        if (lacresSelecionados.length === 0) {
            msgErro.textContent = 'Você precisa selecionar pelo menos um lacre rompido.';
            msgErro.style.display = 'block';
            return;
        }
        msgErro.style.display = 'none';

        lacresRompimentoParaConfirmar = {
            id_equipamento: formData.get('id_equipamento'),
            lacres: []
        };

        const resumoContainer = document.getElementById('resumoLacresRompimento');
        resumoContainer.innerHTML = '';
        let resumoHTML = '<ul>';

        lacresSelecionados.forEach(valor => {
            const [local, numero] = valor.split('|');
            const obs = formData.get(`obs_${local}`) || '';
            const dataRompimento = formData.get(`data_${local}`);
            const dataPsie = formData.get(`psie_${local}`);

            if (!dataRompimento) isDataValid = false;

            lacresRompimentoParaConfirmar.lacres.push({ local, numero, obs, data_rompimento: dataRompimento, data_psie: dataPsie });

            const dataFormatada = new Date(dataRompimento + 'T00:00:00').toLocaleDateString('pt-BR');
            resumoHTML += `<li><strong>${local} (${numero}):</strong> Rompido em ${dataFormatada}`;

            if (dataPsie) {
                const psieFormatada = new Date(dataPsie + 'T00:00:00').toLocaleDateString('pt-BR');
                resumoHTML += `<br><small><em>Reporta no PSIE em: ${psieFormatada}</em></small>`;
            }
            if (obs) {
                resumoHTML += `<br><small><em>Obs: ${obs}</em></small>`;
            }
            resumoHTML += `</li>`;
        });

        if (!isDataValid) {
            msgErro.textContent = 'Por favor, preencha a data de rompimento para todos os lacres selecionados.';
            msgErro.style.display = 'block';
            return;
        }

        resumoHTML += `</ul>`;
        resumoContainer.innerHTML = resumoHTML;

        // Resetar o estado do modal de confirmação 
        document.getElementById('mensagemSalvarRompimento').style.display = 'none';
        document.getElementById('botoesConfirmarRompimento').style.display = 'flex';
        document.getElementById('btnSalvarRompimento').disabled = false;
        document.getElementById('btnSalvarRompimento').querySelector('span').style.display = 'inline';
        document.getElementById('btnSalvarRompimento').querySelector('.spinner').classList.add('hidden');

        fecharModal('modalLacreRompido');
        abrirModal('modalConfirmarRompimento');
    };

    // Envia os dados para a API
    window.executarRompimento = async () => {
        const btnConfirmar = document.getElementById('btnSalvarRompimento');
        const spinner = btnConfirmar.querySelector('.spinner');
        const span = btnConfirmar.querySelector('span');
        const msgDiv = document.getElementById('mensagemSalvarRompimento');
        const btnContainer = document.getElementById('botoesConfirmarRompimento');

        btnConfirmar.disabled = true;
        span.style.display = 'none';
        spinner.classList.remove('hidden');
        msgDiv.style.display = 'none';

        try {
            const response = await fetch('API/registrar_lacre_rompido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(lacresRompimentoParaConfirmar)
            });
            const result = await response.json();

            if (!result.success) { throw new Error(result.message); }

            btnContainer.style.display = 'none';
            msgDiv.className = 'mensagem sucesso';
            msgDiv.textContent = result.message;
            msgDiv.style.display = 'block';

            setTimeout(() => {
                fecharModal('modalConfirmarRompimento');
                buscarEquipamentosELacres();
            }, 2000);

        } catch (error) {
            msgDiv.className = 'mensagem erro';
            msgDiv.textContent = `Erro: ${error.message}`;
            msgDiv.style.display = 'block';
            btnConfirmar.disabled = false;
            span.style.display = 'inline';
            spinner.classList.add('hidden');
        }
    };

    const dbValueToFormName = {};
    for (const formName in LacreMap) {
        dbValueToFormName[LacreMap[formName].dbValue] = formName;
    }
    dbValueToFormName['camera pam'] = 'camera_pam_ab';

    const campoPesquisa = document.getElementById('campoPesquisa');
    const containerFiltroCidades = document.getElementById('containerFiltroCidades');
    const containerListaLacres = document.getElementById('containerListaLacres');
    const mainLoadingState = document.getElementById('mainLoadingState');
    const filtroTipoData = document.getElementById('filtroTipoData');
    const dataInicio = document.getElementById('dataInicio');
    const dataFim = document.getElementById('dataFim');
    const btnFiltrar = document.getElementById('btnFiltrar');
    const btnLimpar = document.getElementById('btnLimpar');

    const btnVoltarAoTopo = document.getElementById("btnVoltarAoTopo");


    window.abrirModal = (id) => document.getElementById(id).classList.add('esta-ativo');
    window.fecharModal = (id) => {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('esta-ativo');

        // Lógica específica para resetar o modal de confirmação ao fechar
        if (id === 'modalConfirmacao') {
            const msgDiv = document.getElementById('mensagemSalvar');
            const btnContainer = document.getElementById('confirmacaoBotoesContainer');
            const btnConfirmar = document.getElementById('btnConfirmarSalvar');
            const btnSpan = btnConfirmar.querySelector('span');
            const btnSpinner = btnConfirmar.querySelector('.spinner');

            // Garante que da próxima vez o modal estará no estado inicial
            setTimeout(() => { 
                msgDiv.style.display = 'none';
                btnContainer.style.display = 'flex';
                btnContainer.querySelectorAll('button').forEach(b => b.disabled = false);
                btnSpan.classList.remove('hidden');
                btnSpinner.classList.add('hidden');
            }, 500);
        }
    };

    window.toggleCameras = (num, btnElement, groupPrefix) => {
        const form = btnElement.closest('form');
        const buttonGroup = btnElement.parentElement;
        buttonGroup.querySelectorAll('button').forEach(btn => btn.classList.remove('ativo'));

        const activeButton = num === 1 ? buttonGroup.children[0] : buttonGroup.children[1];
        if (activeButton) activeButton.classList.add('ativo');

        const unica = form.querySelector(`#${groupPrefix}_camera_unica`);
        const dupla = form.querySelector(`#${groupPrefix}_camera_dupla`);

        const inputAB = unica.querySelector('input');
        const inputA = dupla.querySelector('input[name*="_a"]');
        const inputB = dupla.querySelector('input[name*="_b"]');

        if (num === 1) {
            unica.classList.remove('hidden');
            dupla.classList.add('hidden');
            if (inputA) inputA.value = '';
            if (inputB) inputB.value = '';
        } else {
            unica.classList.add('hidden');
            dupla.classList.remove('hidden');
            if (inputAB) inputAB.value = '';
        }
    };

    window.toggleRompido = (btn, isRompido) => {
        // Primeiro, encontra o container principal do lacre
        const formGroup = btn.closest('.camera-sub-group') || btn.closest('.form-lacre-group');

        // Agora, encontra cada elemento específico por sua classe ou atributo, não pela posição
        const obsTextarea = formGroup.querySelector('textarea[name^="obs_"]');
        const dataRompimentoContainer = formGroup.querySelector('.data-rompimento-container');
        const dataPsieContainer = formGroup.querySelector('.data-psie-container');
        const dataFixacaoInput = formGroup.querySelector('input[name^="dt_fixacao_"]');
        const dataFixacaoLabel = dataFixacaoInput ? dataFixacaoInput.previousElementSibling : null;

        // Lógica para ativar/desativar o botão
        const container = btn.closest('.botoes-toggle');
        container.querySelectorAll('button').forEach(b => b.classList.remove('ativo'));
        btn.classList.add('ativo');
        container.dataset.rompido = isRompido;

        // Aplica a lógica de mostrar/esconder nos elementos corretos
        if (isRompido) {
            if (obsTextarea) obsTextarea.classList.remove('hidden');
            if (dataRompimentoContainer) dataRompimentoContainer.classList.remove('hidden');
            if (dataPsieContainer) dataPsieContainer.classList.remove('hidden');

            // Esconde a data de fixação
            if (dataFixacaoInput) dataFixacaoInput.style.display = 'none';
            if (dataFixacaoLabel && dataFixacaoLabel.tagName === 'LABEL') dataFixacaoLabel.style.display = 'none';

        } else {
            if (obsTextarea) {
                obsTextarea.classList.add('hidden');
                obsTextarea.value = '';
            }
            if (dataRompimentoContainer) {
                dataRompimentoContainer.classList.add('hidden');
                const dataInput = dataRompimentoContainer.querySelector('input[type="date"]');
                if (dataInput) dataInput.value = '';
            }
            if (dataPsieContainer) {
                dataPsieContainer.classList.add('hidden');
                const psieInput = dataPsieContainer.querySelector('input[type="date"]');
                if (psieInput) psieInput.value = '';
            }

            // Mostra a data de fixação novamente
            if (dataFixacaoInput) dataFixacaoInput.style.display = 'block';
            if (dataFixacaoLabel && dataFixacaoLabel.tagName === 'LABEL') dataFixacaoLabel.style.display = 'block';
        }
    };


    function showSidebarAlerts(alerts) {
        const container = document.getElementById('lista-alertas');
        container.innerHTML = ''; 

        if (alerts.length === 0) {
            container.innerHTML = '<p style="padding: 1.5rem; text-align: center; color: #6c757d;">Nenhum alerta no momento.</p>';
            return;
        }

        alerts.forEach(item => {
            const alertElement = document.createElement('a');
            alertElement.className = 'alerta-item';
            alertElement.href = `#equipamento-${item.id_equipamento}`;

            if (item.type === 'rompido') {
                alertElement.classList.add('alerta-psie'); 

                let lacresInfoHTML = '';
                // Itera sobre cada lacre rompido e cria uma linha de detalhe
                item.lacres_rompidos.forEach(lacre => {
                    const formName = dbValueToFormName[lacre.local_lacre.trim()];
                    const displayName = formName ? LacreMap[formName].displayName : lacre.local_lacre;

                    lacresInfoHTML += `<p class="detalhe-alerta">Lacre ${displayName} Rompido: ${lacre.num_lacre_rompido}`;
                    // Se tiver data de PSIE, adiciona a informação
                    if (lacre.dt_reporta_psie) {
                        const psieDate = new Date(lacre.dt_reporta_psie + 'T00:00:00').toLocaleDateString('pt-BR');
                        lacresInfoHTML += ` (Reporta PSIE: ${psieDate})`;
                    }
                    lacresInfoHTML += `</p>`;
                });

                alertElement.innerHTML = `
                <strong>${item.nome_equip}</strong>
                <p>${item.referencia_equip} - ${item.cidade_nome || 'N/A'}</p>
                ${lacresInfoHTML}
            `;
            } else { // Vencimento
                alertElement.classList.add('alerta-vencimento');
                const vencimento = new Date(item.dt_vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
                alertElement.innerHTML = `
                <strong>${item.nome_equip}</strong>
                <p>${item.referencia_equip} - ${item.cidade_nome || 'N/A'}</p>
                <p class="detalhe-alerta">Vencimento Próximo: ${vencimento}</p>
            `;
            }
            container.appendChild(alertElement);
        });
    }

    // bloco para controlar o botão de ocultar/mostrar
    document.getElementById('toggle-sidebar-btn').addEventListener('click', () => {
        document.getElementById('sidebar-alertas').classList.toggle('collapsed');
    });

    async function carregarDadosIniciais() {
        // 1. Primeiro, busca todos os equipamentos para sabermos quais cidades têm equipamentos
        await buscarEquipamentosELacres(true);
        // 2. Depois, cria os filtros de cidade com base nos dados retornados
        carregarFiltrosDeCidade(todosOsEquipamentos);
    }

    function carregarFiltrosDeCidade(equipamentos) {
        // Extrai nomes de cidades únicos e remove valores nulos/vazios
        const nomesCidades = [...new Set(equipamentos.map(e => e.cidade_nome).filter(Boolean))];
        nomesCidades.sort(); // Ordena alfabeticamente

        containerFiltroCidades.innerHTML = '';
        const btnTodas = document.createElement('button');
        btnTodas.textContent = 'Todas';
        btnTodas.className = 'ativo';
        btnTodas.onclick = () => setFiltroCidade('Todas');
        containerFiltroCidades.appendChild(btnTodas);

        nomesCidades.forEach(nomeCidade => {
            const btn = document.createElement('button');
            btn.textContent = nomeCidade;
            btn.onclick = () => setFiltroCidade(nomeCidade);
            containerFiltroCidades.appendChild(btn);
        });
    }

    function setFiltroCidade(nomeCidade) {
        filtroCidadeAtivo = nomeCidade;
        document.querySelectorAll('#containerFiltroCidades button').forEach(btn => {
            btn.classList.toggle('ativo', btn.textContent === nomeCidade);
        });
        renderizarLista();
    }

    async function buscarEquipamentosELacres(showNotif = false) {
        mainLoadingState.style.display = 'flex';
        containerListaLacres.innerHTML = '';
        const params = new URLSearchParams({
            search: campoPesquisa.value,
            date_type: filtroTipoData.value,
            start_date: dataInicio.value,
            end_date: dataFim.value,
        });
        try {
            const response = await fetch(`API/get_equipamentos_lacres.php?${params.toString()}`);
            const data = await response.json();
            if (data.success) {
                todosOsEquipamentos = data.equipamentos;
                renderizarLista();
                if (showNotif) {
                    // 1. Busca os alertas de prioridade alta (Rompido)
                    const rompidoItems = getRompidoItems(todosOsEquipamentos);

                    // 2. Busca os alertas de prioridade baixa (Vencimento)
                    const expiringItems = getExpiringItems(todosOsEquipamentos).map(it => ({ ...it, type: 'vencimento' }));

                    // 3. Lógica de Prioridade: Remove da lista de vencimento qualquer equipamento que já esteja na lista de rompido
                    const rompidoIds = new Set(rompidoItems.map(item => item.id_equipamento));
                    const uniqueExpiringItems = expiringItems.filter(item => !rompidoIds.has(item.id_equipamento));

                    // 4. Junta os arrays, com os de rompido sempre primeiro
                    const allAlerts = [...rompidoItems, ...uniqueExpiringItems];

                    // 5. Chama a função da barra lateral com a lista final e priorizada
                    showSidebarAlerts(allAlerts);
                }
            } else {
                containerListaLacres.innerHTML = `<p class="mensagem erro">${data.message}</p>`;
            }
        } catch (error) {
            containerListaLacres.innerHTML = `<p class="mensagem erro">Erro de conexão ao buscar dados.</p>`;
        } finally {
            mainLoadingState.style.display = 'none';
        }
    }

    function getExpiringItems(equipments) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const twoMonthsFromNow = new Date();
        twoMonthsFromNow.setMonth(twoMonthsFromNow.getMonth() + 2);
        return equipments.filter(equip => {
            if (!equip.dt_vencimento) return false;
            const expirationDate = new Date(equip.dt_vencimento + 'T00:00:00');
            return expirationDate >= today && expirationDate <= twoMonthsFromNow;
        });
    }

    function getRompidoItems(equipments) {
        const rompidoAlerts = [];

        equipments.forEach(equip => {
            if (equip.lacres && equip.lacres.length > 0) {
                // Filtra para encontrar todos os lacres rompidos neste equipamento
                const brokenSeals = equip.lacres.filter(lacre => lacre.lacre_rompido == 1);

                if (brokenSeals.length > 0) {
                    // Se encontrou algum, cria um alerta para o equipamento
                    rompidoAlerts.push({
                        ...equip, // Copia todas as propriedades do equipamento
                        type: 'rompido',
                        // Adiciona uma nova propriedade com a lista de lacres rompidos
                        lacres_rompidos: brokenSeals
                    });
                }
            }
        });
        return rompidoAlerts;
    }

    function renderizarLista() {
        containerListaLacres.innerHTML = '';
        const termoPesquisa = campoPesquisa.value.toLowerCase();

        // Função auxiliar para verificar se todos os lacres estão preenchidos
        const verificarTodosPreenchidos = (lacres) => {
            if (!lacres || lacres.length === 0) return false;

            const locaisPreenchidos = new Set(
                lacres
                    .filter(l => l.lacre_afixado == 1 || l.lacre_distribuido == 1 || l.lacre_rompido == 1)
                    .map(l => l.local_lacre.toLowerCase())
            );

            const requiredLocations = new Set(['metrologico', 'nao metrologico', 'fonte', 'switch']);


            const lacresZoom = lacres.filter(l => l.local_lacre.toLowerCase().startsWith('camera zoom'));
            if (lacresZoom.length > 0) {
                if (lacresZoom.some(l => l.local_lacre.toLowerCase().includes('(fx. a/b)'))) {
                    requiredLocations.add('camera zoom (fx. a/b)');
                } else {
                    // Se existir qualquer lacre de zoom (A ou B), a configuração é de duas câmeras.
                    requiredLocations.add('camera zoom (fx. a)');
                    requiredLocations.add('camera zoom (fx. b)');
                }
            }


            const lacresPam = lacres.filter(l => l.local_lacre.toLowerCase().startsWith('camera pam'));
            if (lacresPam.length > 0) {
                if (lacresPam.some(l => l.local_lacre.toLowerCase().includes('(fx. a/b)'))) {
                    requiredLocations.add('camera pam (fx. a/b)');
                } else {
                    // Se existir qualquer lacre de pam (A ou B), a configuração é de duas câmeras.
                    requiredLocations.add('camera pam (fx. a)');
                    requiredLocations.add('camera pam (fx. b)');
                }
            }

            // A verificação final agora funciona corretamente para todas as configurações
            return [...requiredLocations].every(loc => locaisPreenchidos.has(loc));
        };


        const equipamentosFiltrados = todosOsEquipamentos.filter(equip => {
            const correspondeCidade = filtroCidadeAtivo === 'Todas' || equip.cidade_nome === filtroCidadeAtivo;
            const correspondePesquisa = !termoPesquisa ||
                (equip.nome_equip && equip.nome_equip.toLowerCase().includes(termoPesquisa)) ||
                (equip.referencia_equip && equip.referencia_equip.toLowerCase().includes(termoPesquisa));
            return correspondeCidade && correspondePesquisa;
        });

        if (equipamentosFiltrados.length === 0) {
            containerListaLacres.innerHTML = '<p class="mensagem">Nenhum equipamento encontrado.</p>';
            return;
        }

        const expiringItems = getExpiringItems(todosOsEquipamentos);
        const equipamentosAgrupados = equipamentosFiltrados.reduce((acc, equip) => {
            const cidade = equip.cidade_nome || 'Sem Cidade';
            if (!acc[cidade]) acc[cidade] = [];
            acc[cidade].push(equip);
            return acc;
        }, {});

        for (const cidade in equipamentosAgrupados) {
            const grupoDiv = document.createElement('div');
            grupoDiv.className = 'cidade-grupo';
            grupoDiv.innerHTML = `<h2>${cidade}</h2>`;

            equipamentosAgrupados[cidade].forEach(equip => {
                const isExpiring = expiringItems.some(item => item.id_equipamento === equip.id_equipamento);
                const itemClass = isExpiring ? 'item-equipamento-lacre vencimento-proximo' : 'item-equipamento-lacre';
                const vencimentoClass = isExpiring ? 'vencimento-proximo-texto' : '';

                const todosPreenchidos = verificarTodosPreenchidos(equip.lacres);
                const temAlgumLacre = equip.lacres && equip.lacres.length > 0;
                let buttonText = 'Adicionar Lacres';
                if (todosPreenchidos) {
                    buttonText = 'Editar Lacres';
                } else if (temAlgumLacre) {
                    buttonText = 'Afixar Lacre';
                }
                

                const lacresValidos = equip.lacres ? equip.lacres.filter(lacre => lacre.local_lacre && lacre.local_lacre.trim() !== '') : [];

                const lacresHTML = lacresValidos.length > 0 ? lacresValidos.map(lacre => {
                    const localLacreLimpo = lacre.local_lacre.trim();
                    const formName = dbValueToFormName[localLacreLimpo];
                    const displayName = formName ? LacreMap[formName].displayName : localLacreLimpo;

                    let itemLacreHTML = `<div class="lacre-item-container">`; // 1. Abre o lacre-item-container

                    // 2. Adiciona o botão de edição 
                    // Passa todos os dados do lacre para a função JS
                    const lacreDataString = JSON.stringify(lacre).replace(/"/g, '&quot;');
                    itemLacreHTML += `<button class="botao-editar-lacre" onclick='abrirModalEdicaoIndividual(${lacreDataString})'><i class="fas fa-pencil-alt"></i></button>`;

                    itemLacreHTML += `<div class="lacre-item">`; // 3. USA "+=" para adicionar o conteúdo interno
                    itemLacreHTML += `<strong>${displayName}:</strong> `;

                    if (lacre.lacre_afixado == 1 && lacre.num_lacre) {
                        itemLacreHTML += lacre.num_lacre;
                    } else if (lacre.lacre_rompido == 1 && lacre.num_lacre_rompido) {
                        itemLacreHTML += `<span class="lacre-detalhe rompido">Lacre Rompido: ${lacre.num_lacre_rompido}</span>`;
                        if (lacre.dt_reporta_psie) {
                            const dataPsieFormatada = new Date(lacre.dt_reporta_psie + 'T00:00:00').toLocaleDateString('pt-BR');
                            itemLacreHTML += `<span class="lacre-detalhe rompido">Reporta no PSIE: ${dataPsieFormatada}</span>`;
                        }
                    } else if (lacre.lacre_distribuido == 1 && lacre.num_lacre_distribuido) {
                        itemLacreHTML += `<span class="lacre-detalhe distribuido">Distribuído: ${lacre.num_lacre_distribuido}</span>`;
                    } else {
                        itemLacreHTML += `(Vazio)`;
                    }

                    if (lacre.lacre_afixado != 1 && lacre.obs_lacre) {
                        itemLacreHTML += `<span class="lacre-detalhe">Obs: ${lacre.obs_lacre}</span>`;
                    }

                    itemLacreHTML += `</div>`; // 4. Fecha o lacre-item
                    itemLacreHTML += `</div>`; // 5. Fecha o lacre-item-container

                    return itemLacreHTML;
                }).join('') : '<p>Nenhum lacre cadastrado.</p>';

                const lacresData = JSON.stringify(equip.lacres).replace(/"/g, '&quot;');
                const vencimentoTexto = equip.dt_vencimento ? new Date(equip.dt_vencimento + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A';

                const itemHTML = `<div id="equipamento-${equip.id_equipamento}" class="${itemClass}"><div class="equipamento-info"><div class="info-bloco"><h4>Equipamento</h4><p class="equipamento-identificacao">${equip.nome_equip} - ${equip.referencia_equip}</p></div><div class="info-bloco"><h4>Detalhes</h4><p><strong>Qtd. Faixas:</strong> ${equip.qtd_faixa || 'N/A'}</p><p><strong>KM via:</strong> ${equip.km || 'N/A'}</p></div><div class="info-bloco"><h4>Aferição</h4><p><strong>N° Instrumento:</strong> ${equip.num_instrumento || 'N/A'}</p><p><strong>Data Aferição:</strong> ${equip.dt_afericao ? new Date(equip.dt_afericao + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A'}</p><p class="${vencimentoClass}"><strong>Vencimento:</strong> ${vencimentoTexto}</p></div></div><h4>Lacres</h4><div class="lacres-grid">${lacresHTML}</div><div class="equipamento-actions"><button class="botao-distribuir-lacres botao-lacre-rompido" style="background-color: #cff4fc; color: #055160; border: 1px solid #b6effb;" data-equip-id="${equip.id_equipamento}" data-equip-name="${equip.nome_equip}" data-lacres="${lacresData}" onclick="abrirModalDistribuir(this)">Distribuir Lacre</button><button class="botao-lacre-rompido" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;" data-equip-id="${equip.id_equipamento}" data-equip-name="${equip.nome_equip}" onclick="abrirModalLacreRompido(this)">Romper Lacre</button><button class="botao-adicionar-lacres" data-equip-id="${equip.id_equipamento}" data-equip-name="${equip.nome_equip}" data-qtd-faixa="${equip.qtd_faixa || ''}"data-lacres="${lacresData}" onclick="abrirModalLacres(this)">${buttonText}</button></div></div>`;
                grupoDiv.innerHTML += itemHTML;
            });
            containerListaLacres.appendChild(grupoDiv);
        }
    };


    window.abrirModalLacres = (btn) => {
        const buttonText = btn.textContent;
        const form = document.getElementById('formularioAdicionarLacres');

        if (buttonText === 'Editar Lacres') {
            // 1. Guarda o botão que foi clicado
            botaoEditarPressionado = btn;
            // 2. Abre o nosso novo modal de confirmação
            abrirModal('modalConfirmarEdicao');
            // A lógica de edição foi movida para a função 'prosseguirComEdicao'
            return; 
        }

        
        const lacresAtuais = btn.dataset.lacres ? JSON.parse(btn.dataset.lacres.replace(/&quot;/g, '"')) : [];
        form.reset();
        form.querySelectorAll('.form-lacre-group, .camera-sub-group').forEach(el => {
            el.style.display = 'block';
            el.className = el.className.replace(/ is-\w+/g, '');
            const statusDiv = el.querySelector('.lacre-status');
            if (statusDiv) statusDiv.remove();
        });
        form.querySelectorAll('input[name^="dt_fixacao_"]').forEach(input => {
            input.style.display = 'block';
            const label = input.previousElementSibling;
            if (label && label.tagName === 'LABEL') label.style.display = 'block';
        });
        form.querySelectorAll('.rompido-toggle-container').forEach(el => {
            el.style.display = 'flex';
        });
        form.querySelectorAll('.data-rompimento-container, .data-psie-container, .obs-lacre').forEach(el => el.classList.add('hidden'));
        form.querySelectorAll('.botoes-toggle').forEach(container => {
            container.dataset.rompido = 'false';
            const buttons = container.querySelectorAll('button');
            if (buttons.length === 2 && container.closest('.rompido-toggle-container')) {
                buttons[0].classList.add('ativo');
                buttons[1].classList.remove('ativo');
            }
        });

        operacaoAtual = 'add';
        document.getElementById('tituloModalAdicionarLacres').textContent = `${buttonText} para: ${btn.dataset.equipName}`;
        form.querySelector('[name="id_equipamento"]').value = btn.dataset.equipId;

        if (buttonText === 'Adicionar Lacres') {
            toggleCameras(1, form.querySelector('.zoom-toggle button'), 'zoom');
            toggleCameras(1, form.querySelector('.pam-toggle button'), 'pam');
            abrirModal('modalAdicionarLacres');
            return;
        }

        const locaisPreenchidos = new Set(
            lacresAtuais
                .filter(lacre => lacre.lacre_afixado == 1 || lacre.lacre_distribuido == 1 || lacre.lacre_rompido == 1)
                .map(lacre => lacre.local_lacre.toLowerCase())
        );

        form.querySelectorAll('.form-lacre-group').forEach(group => {
            if (group.querySelector('.zoom-toggle') || group.querySelector('.pam-toggle')) {
                return;
            }
            const input = group.querySelector('input[type="text"]');
            if (!input) return;
            const formName = input.name;
            const lacreInfo = LacreMap[formName];
            if (lacreInfo && locaisPreenchidos.has(lacreInfo.dbValue.toLowerCase())) {
                group.style.display = 'none';
            }
        });

        ['zoom', 'pam'].forEach(groupPrefix => {
            const mainGroupContainer = form.querySelector(`.${groupPrefix}-toggle`).closest('.form-lacre-group');
            let requiredCameraSlots = [];
            if (lacresAtuais.some(l => l.local_lacre.toLowerCase() === `camera ${groupPrefix} (fx. a/b)`)) {
                requiredCameraSlots.push(`camera ${groupPrefix} (fx. a/b)`);
            } else if (lacresAtuais.some(l => l.local_lacre.toLowerCase().startsWith(`camera ${groupPrefix} (fx.`))) {
                requiredCameraSlots.push(`camera ${groupPrefix} (fx. a)`);
                requiredCameraSlots.push(`camera ${groupPrefix} (fx. b)`);
            }
            const allCameraSlotsFilled = requiredCameraSlots.length > 0 && requiredCameraSlots.every(slot => locaisPreenchidos.has(slot));
            if (allCameraSlotsFilled) {
                mainGroupContainer.style.display = 'none';
            } else {
                mainGroupContainer.style.display = 'block';
                const lacresMap = new Map(lacresAtuais.map(l => [l.local_lacre.toLowerCase(), l]));
                const camA = lacresMap.has(`camera ${groupPrefix} (fx. a)`);
                const camB = lacresMap.has(`camera ${groupPrefix} (fx. b)`);
                if (camA || camB) {
                    toggleCameras(2, form.querySelector(`.${groupPrefix}-toggle button`), groupPrefix);
                } else {
                    toggleCameras(1, form.querySelector(`.${groupPrefix}-toggle button`), groupPrefix);
                }
                const duplaContainer = form.querySelector(`#${groupPrefix}_camera_dupla`);
                if (duplaContainer) {
                    duplaContainer.querySelectorAll('.camera-sub-group').forEach(subGroup => {
                        const input = subGroup.querySelector('input[type="text"]');
                        const formName = input.name;
                        const lacreInfo = LacreMap[formName];
                        if (lacreInfo && locaisPreenchidos.has(lacreInfo.dbValue.toLowerCase())) {
                            subGroup.style.display = 'none';
                        }
                    });
                }
            }
        });
        abrirModal('modalAdicionarLacres');
    };


    function prosseguirComEdicao() {
        const btn = botaoEditarPressionado;
        if (!btn) return; 

        const lacresAtuais = btn.dataset.lacres ? JSON.parse(btn.dataset.lacres.replace(/&quot;/g, '"')) : [];
        const form = document.getElementById('formularioAdicionarLacres');

        operacaoAtual = 'substitute';
        document.getElementById('tituloModalAdicionarLacres').textContent = `Editar Lacres para: ${btn.dataset.equipName}`;
        form.querySelector('[name="id_equipamento"]').value = btn.dataset.equipId;
        form.reset();
        form.querySelectorAll('.form-lacre-group, .camera-sub-group').forEach(el => {
            el.style.display = 'block';
            el.className = el.className.replace(/ is-\w+/g, '');
            const statusDiv = el.querySelector('.lacre-status');
            if (statusDiv) statusDiv.remove();
        });
        form.querySelectorAll('.rompido-toggle-container').forEach(el => el.style.display = 'flex');
        form.querySelectorAll('.data-rompimento-container, .data-psie-container, .obs-lacre').forEach(el => el.classList.add('hidden'));
        form.querySelectorAll('input[name^="dt_fixacao_"]').forEach(input => {
            input.style.display = 'block';
            const label = input.previousElementSibling;
            if (label && label.tagName === 'LABEL') label.style.display = 'block';
        });
        form.querySelectorAll('.botoes-toggle').forEach(container => {
            const btnNao = container.querySelector('button');
            if (btnNao) toggleRompido(btnNao, false);
        });
        lacresAtuais.forEach(lacre => {
            const formName = dbValueToFormName[lacre.local_lacre.trim()];
            if (!formName) return;
            const input = form.querySelector(`[name="${formName}"]`);
            if (!input) return;
            input.dataset.idLacre = lacre.id_controle_lacres || '';
            const group = input.closest('.camera-sub-group') || input.closest('.form-lacre-group');
            if (!group) return;
            const statusDiv = document.createElement('div');
            statusDiv.className = 'lacre-status';
            const rompidoContainer = group.querySelector('.rompido-toggle-container');
            const dataFixacaoInput = group.querySelector('input[name^="dt_fixacao_"]');
            const dataFixacaoLabel = dataFixacaoInput ? dataFixacaoInput.previousElementSibling : null;
            if (lacre.lacre_rompido == 1) {
                group.classList.add('is-rompido');
                statusDiv.textContent = 'Rompido';
                group.prepend(statusDiv);
                input.value = lacre.num_lacre_rompido || '';
                const obs = group.querySelector('textarea[name^="obs_"]');
                if (obs) {
                    obs.value = lacre.obs_lacre || '';
                    obs.classList.remove('hidden');
                }
                const dataRompContainer = group.querySelector('.data-rompimento-container');
                if (dataRompContainer) {
                    dataRompContainer.classList.remove('hidden');
                    const dtRompInput = dataRompContainer.querySelector('input[name^="dt_rompimento_"]');
                    if (dtRompInput) dtRompInput.value = lacre.dt_rompimento || '';
                }
                const dataPsieContainer = group.querySelector('.data-psie-container');
                if (dataPsieContainer) {
                    dataPsieContainer.classList.remove('hidden');
                    const dtPsieInput = dataPsieContainer.querySelector('input[name^="dt_reporta_psie_"]');
                    if (dtPsieInput) dtPsieInput.value = lacre.dt_reporta_psie || '';
                }
                if (rompidoContainer) rompidoContainer.style.display = 'none';
                if (dataFixacaoInput) dataFixacaoInput.style.display = 'none';
                if (dataFixacaoLabel) dataFixacaoLabel.style.display = 'none';
            } else if (lacre.lacre_distribuido == 1) {
                group.classList.add('is-distribuido');
                statusDiv.textContent = 'Distribuído';
                group.prepend(statusDiv);
                input.value = lacre.num_lacre_distribuido || '';
                const obs = group.querySelector('textarea[name^="obs_"]');
                if (obs) {
                    obs.value = lacre.obs_lacre || '';
                    obs.classList.remove('hidden');
                }
                if (rompidoContainer) rompidoContainer.style.display = 'none';
                if (dataFixacaoInput) dataFixacaoInput.style.display = 'none';
                if (dataFixacaoLabel) dataFixacaoLabel.style.display = 'none';
                const dataRompContainer = group.querySelector('.data-rompimento-container');
                if (dataRompContainer) dataRompContainer.classList.add('hidden');
                const dataPsieContainer = group.querySelector('.data-psie-container');
                if (dataPsieContainer) dataPsieContainer.classList.add('hidden');
            } else if (lacre.lacre_afixado == 1) {
                input.value = lacre.num_lacre || '';
                if (dataFixacaoInput) dataFixacaoInput.value = lacre.dt_fixacao || '';
                const btnNao = group.querySelector('.botoes-toggle button:first-child');
                if (btnNao) toggleRompido(btnNao, false);
            }
        });
        ['zoom', 'pam'].forEach(p => {
            if (lacresAtuais.some(l => l.local_lacre.toLowerCase().includes(`${p} (fx. a)`))) {
                toggleCameras(2, form.querySelector(`.${p}-toggle button`), p);
            } else {
                toggleCameras(1, form.querySelector(`.${p}-toggle button`), p);
            }
        });
        abrirModal('modalAdicionarLacres');
    }

    
    document.getElementById('btnExecutarEdicao').addEventListener('click', () => {
        // 1. Fecha o modal de confirmação
        fecharModal('modalConfirmarEdicao');
        // 2. Executa a função que abre o modal de edição principal
        prosseguirComEdicao();
    });

    window.prepararEnvio = (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        lacresParaConfirmar = { id_equipamento: formData.get('id_equipamento'), lacres: [] };
        const resumo = document.getElementById('resumoLacres');
        resumo.innerHTML = '';
        let temLacre = false;

        for (const formName in LacreMap) {
            const lacreInfo = LacreMap[formName];
            const inputDoLacre = form.querySelector(`[name="${formName}"]`);

            if (inputDoLacre) {
                const groupContainer = inputDoLacre.closest('.form-lacre-group, .camera-sub-group');
                if (groupContainer && (groupContainer.classList.contains('is-rompido') || groupContainer.classList.contains('is-distribuido'))) {
                    continue;
                }
            }

            const numeroLacre = formData.get(formName);

            if (numeroLacre && numeroLacre.trim() !== '') {
                temLacre = true;

                const dateFixacaoFieldName = `dt_fixacao_${formName}`;
                const dataFixacao = formData.get(dateFixacaoFieldName) || null;

                const obs = formData.get(`obs_${formName}`) || '';

                let isRompido = false;
                const inputDoLacre = form.querySelector(`[name="${formName}"]`);

                if (inputDoLacre) {
                    const groupContainer = inputDoLacre.closest('.form-lacre-group') || inputDoLacre.closest('div[id*="_camera_"]');
                    const obsTextarea = groupContainer?.querySelector(`textarea[name="obs_${formName}"]`);
                    const rompidoContainer = obsTextarea?.previousElementSibling;
                    const rompidoDiv = rompidoContainer?.querySelector('.botoes-toggle');
                    if (rompidoDiv) {
                        isRompido = rompidoDiv.dataset.rompido === 'true';
                    }
                }

                const dateRompimentoFieldName = `dt_rompimento_${formName}`;
                const dataRompimento = isRompido ? formData.get(dateRompimentoFieldName) : null;

                const datePsieFieldName = `dt_reporta_psie_${formName}`;
                const dataPsie = isRompido ? formData.get(datePsieFieldName) : null;


                lacresParaConfirmar.lacres.push({
                    id: inputDoLacre.dataset.idLacre || null,
                    local: lacreInfo.dbValue,
                    numero: numeroLacre,
                    rompido: isRompido,
                    obs: obs,
                    dt_fixacao: isRompido ? null : dataFixacao,
                    dt_rompimento: dataRompimento,
                    dt_reporta_psie: dataPsie
                });

                let resumoHTML = `<p><strong>${lacreInfo.displayName}:</strong> ${numeroLacre}`;
                if (isRompido) {
                    resumoHTML += ` <span style="color: #c81e1e; font-weight: bold;">(Rompido)</span>`;
                    if (dataRompimento) {
                        resumoHTML += ` <span style="font-size: 0.9em;">- Rompido em: ${new Date(dataRompimento + 'T00:00:00').toLocaleDateString('pt-BR')}</span>`;
                    }
                    if (obs) {
                        resumoHTML += `<br><em style="font-size: 0.9em;">Obs: ${obs}</em>`;
                    }
                    if (dataPsie) {
                        resumoHTML += `<br><em style="font-size: 0.9em;">Reporta no PSIE em: ${new Date(dataPsie + 'T00:00:00').toLocaleDateString('pt-BR')}</em>`;
                    }
                } else if (dataFixacao) {
                    resumoHTML += ` (Fixado em: ${new Date(dataFixacao + 'T00:00:00').toLocaleDateString('pt-BR')})`;
                }
                resumoHTML += `</p>`;
                resumo.innerHTML += resumoHTML;
            }
        }

        if (temLacre) {
            let tituloConfirmacao = '';
            if (operacaoAtual === 'substitute') {
                tituloConfirmacao = 'Confirmar Substituição';
            } else if (operacaoAtual === 'add') {
                tituloConfirmacao = 'Confirmar Novos Lacres';
            } else if (operacaoAtual === 'distribute') {
                tituloConfirmacao = 'Confirmar Lacres a Distribuir';
            }
            document.getElementById('tituloConfirmacao').textContent = tituloConfirmacao;
            fecharModal('modalAdicionarLacres');
            abrirModal('modalConfirmacao');
        } else {
            const msgDiv = document.getElementById('mensagemAdicionarVazio');
            msgDiv.textContent = 'Preencha pelo menos um campo de lacre.';
            msgDiv.style.display = 'block';
            setTimeout(() => { msgDiv.style.display = 'none'; }, 3000);
        }
    };

    function darFeedbackBotoes(botaoAtivo, botaoInativo) {
        botaoAtivo.classList.add('btn-active');
        botaoInativo.classList.add('btn-inactive');
        setTimeout(() => {
            botaoAtivo.classList.remove('btn-active');
            botaoInativo.classList.remove('btn-inactive');
        }, 400);
    }

    campoPesquisa.addEventListener('input', renderizarLista);

    btnFiltrar.addEventListener('click', () => {
        if (filtroTipoData.value && (!dataInicio.value || !dataFim.value)) {
            alert('Por favor, preencha as datas de início e fim para filtrar.');
            return;
        }
        darFeedbackBotoes(btnFiltrar, btnLimpar);
        buscarEquipamentosELacres();
    });

    btnLimpar.addEventListener('click', () => {
        darFeedbackBotoes(btnLimpar, btnFiltrar);
        campoPesquisa.value = '';
        filtroTipoData.value = '';
        dataInicio.value = '';
        dataFim.value = '';
        buscarEquipamentosELacres(true);
    });

    dataInicio.addEventListener('change', () => {
        const dataDe = dataInicio.value;

        if (dataDe) {
            // 1. Trava as datas anteriores no campo "Até"
            dataFim.min = dataDe;

            // o campo "Até" será limpo para evitar um intervalo inválido.
            if (dataFim.value && dataFim.value < dataDe) {
                dataFim.value = '';
            }

            // 2. Abre o calendário do campo "Até" automaticamente
            dataFim.showPicker();

        } else {
            // Se o campo "De" for limpo, remove a restrição de data mínima
            dataFim.min = '';
        }
    });

    document.getElementById('btnConfirmarSalvar').addEventListener('click', async () => {
        let endpoint = '';
        // Define qual script chamar com base na operação
        if (operacaoAtual === 'add') {
            endpoint = 'API/add_lacres.php';
        } else if (operacaoAtual === 'substitute') {
            endpoint = 'API/update_lacres.php';
        } else if (operacaoAtual === 'distribute') {
            endpoint = 'API/distribute_lacres.php'; 
        } else {
            alert('Operação desconhecida!');
            return;
        }

        const msgDiv = document.getElementById('mensagemSalvar');
        const btnContainer = document.getElementById('confirmacaoBotoesContainer');
        const btnConfirmar = document.getElementById('btnConfirmarSalvar');
        const btnSpan = btnConfirmar.querySelector('span');
        const btnSpinner = btnConfirmar.querySelector('.spinner');

        msgDiv.style.display = 'none';
        btnContainer.querySelectorAll('button').forEach(b => b.disabled = true);
        btnSpan.classList.add('hidden');
        btnSpinner.classList.remove('hidden');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(lacresParaConfirmar)
            });
            const result = await response.json();
            if (!result.success) { throw new Error(result.message); }

            btnContainer.style.display = 'none';
            msgDiv.className = 'mensagem sucesso';
            msgDiv.textContent = result.message;
            msgDiv.style.display = 'block';
            setTimeout(() => {
                fecharModal('modalConfirmacao');
                buscarEquipamentosELacres();
            }, 2000);

        } catch (error) {
            msgDiv.className = 'mensagem erro';
            msgDiv.textContent = `Erro: ${error.message}`;
            msgDiv.style.display = 'block';
            btnContainer.querySelectorAll('button').forEach(b => b.disabled = false);
            btnSpan.classList.remove('hidden');
            btnSpinner.classList.add('hidden');
        }
    });

    window.onscroll = function () {
        controlarVisibilidadeBotao();
    };

    function controlarVisibilidadeBotao() {
        // Se a página for rolada mais de 20px para baixo...
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            // ...o botão aparece.
            btnVoltarAoTopo.style.display = "block";
        } else {
            // ...senão, ele desaparece.
            btnVoltarAoTopo.style.display = "none";
        }
    }

    // Adiciona a ação que acontece quando o botão é clicado
    btnVoltarAoTopo.addEventListener('click', function () {
        // Manda a página de volta para o topo de forma suave
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.abrirModalDistribuir = (btn) => {


        const lacresAtuais = btn.dataset.lacres ? JSON.parse(btn.dataset.lacres.replace(/&quot;/g, '"')) : [];


        // 1. Identifica a configuração de câmeras REAL do equipamento com base nos lacres existentes
        const requiredLocations = new Set(['metrologico', 'nao metrologico', 'fonte', 'switch']);
        if (lacresAtuais.some(l => l.local_lacre === 'camera zoom (fx. A/B)')) {
            requiredLocations.add('camera zoom (fx. A/B)');
        } else if (lacresAtuais.some(l => l.local_lacre === 'camera zoom (fx. A)' || l.local_lacre === 'camera zoom (fx. B)')) {
            requiredLocations.add('camera zoom (fx. A)');
            requiredLocations.add('camera zoom (fx. B)');
        }

        if (lacresAtuais.some(l => l.local_lacre === 'camera pam (fx. A/B)')) {
            requiredLocations.add('camera pam (fx. A/B)');
        } else if (lacresAtuais.some(l => l.local_lacre === 'camera pam (fx. A)' || l.local_lacre === 'camera pam (fx. B)')) {
            requiredLocations.add('camera pam (fx. A)');
            requiredLocations.add('camera pam (fx. B)');
        }

        // 2. Identifica quais locais já estão preenchidos (afixados ou com distribuição pendente)
        const filledLocations = new Set(
            lacresAtuais
                .filter(l => (l.lacre_afixado == 1 && l.lacre_rompido == 0) || l.lacre_distribuido == 1)
                .map(l => l.local_lacre)
        );

        // 3. Verifica se TODOS os locais obrigatórios para ESTE equipamento já estão preenchidos
        const allRequiredAreFilled = [...requiredLocations].every(loc => filledLocations.has(loc));

        // 4. Verifica se existe algum lacre rompido que precise de atenção
        const lacresRompido = lacresAtuais.filter(l => l.lacre_rompido == 1);


        // CENÁRIO 1: Se tudo estiver preenchido E não houver nada rompido, exibe o alerta e para.
        if (allRequiredAreFilled && lacresRompido.length === 0) {
            alert('Equipamento com todos os lacres afixados ou com distribuição pendente.');
            return;
        }

        // CENÁRIO 2: Se existem lacres ROMPIDOS, abre o modal de substituição para eles.
        if (lacresRompido.length > 0) {
            const modal = document.getElementById('modalDistribuirRompido');
            const form = document.getElementById('formDistribuirRompido');
            const containerLacres = document.getElementById('listaLacresRompidoDistribuir');
            const containerDetalhes = document.getElementById('detalhesLacresDistribuir');

            form.reset();
            containerDetalhes.innerHTML = '';
            containerDetalhes.classList.add('hidden');
            document.getElementById('mensagemErroDistribuir').style.display = 'none';
            document.getElementById('tituloModalDistribuirRompido').textContent = `Distribuir para Lacres Rompidos - ${btn.dataset.equipName}`;
            form.querySelector('[name="id_equipamento"]').value = btn.dataset.equipId;
            containerLacres.innerHTML = '';

            lacresRompido.forEach(lacre => {
                let statusTexto = `<span style="color: #c81e1e;">(Rompido: ${lacre.num_lacre_rompido})</span>`;
                const div = document.createElement('div');
                div.innerHTML = `<label><input type="checkbox" name="lacres_a_substituir" value="${lacre.local_lacre}"> <strong>${lacre.local_lacre}:</strong> ${statusTexto}</label>`;
                containerLacres.appendChild(div);
            });

            containerLacres.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', () => toggleDetalhesDistribuicao(checkbox));
            });

            abrirModal('modalDistribuirRompido');
            return;
        }

        // CENÁRIO 3: Se chegou até aqui, significa que não há rompidos, mas há locais vazios. Abre o modal de "Adicionar".
        abrirModalLacres(btn);
        operacaoAtual = 'distribute';
        const modalAdicionar = document.getElementById('modalAdicionarLacres');
        modalAdicionar.querySelector('#tituloModalAdicionarLacres').textContent = `Distribuir Lacres para: ${btn.dataset.equipName}`;
        modalAdicionar.querySelectorAll('.rompido-toggle-container').forEach(el => el.style.display = 'none');

        // Esconde os campos que já têm lacres válidos (afixados ou distribuídos)
        const formAdicionar = document.getElementById('formularioAdicionarLacres');
        for (const formName in LacreMap) {
            const lacreInfo = LacreMap[formName];
            const input = formAdicionar.querySelector(`[name="${formName}"]`);
            if (input) {
                const groupContainer = input.closest('.form-lacre-group');
                if (filledLocations.has(lacreInfo.dbValue)) {
                    if (groupContainer) groupContainer.style.display = 'none';
                } else {
                    if (groupContainer) groupContainer.style.display = 'block';
                }
            }
        }
        modalAdicionar.querySelectorAll('input[name^="dt_fixacao_"]').forEach(input => {
            input.style.display = 'none';
            const label = input.previousElementSibling;
            if (label && label.tagName === 'LABEL' && label.textContent.includes('Data de Fixação')) {
                label.style.display = 'none';
            }
        });
    };

    function toggleDetalhesDistribuicao(checkbox) {
        const containerDetalhes = document.getElementById('detalhesLacresDistribuir');
        const local = checkbox.value;
        const detalheId = `detalhe-dist-${local.replace(/[^a-zA-Z0-9]/g, '')}`;

        if (checkbox.checked) {
            containerDetalhes.classList.remove('hidden');
            const div = document.createElement('div');
            div.id = detalheId;
            div.className = 'form-lacre-group';
            div.innerHTML = `
            <label for="num_${local}">Novo Lacre para: ${local}</label>
            <input type="text" name="num_${local}" placeholder="Digite o n° do novo lacre" required>
            <textarea name="obs_${local}" class="obs-lacre" placeholder="Observações (opcional)..."></textarea>
        `;
            containerDetalhes.appendChild(div);
        } else {
            const detalheExistente = document.getElementById(detalheId);
            if (detalheExistente) detalheExistente.remove();
            if (containerDetalhes.children.length === 0) containerDetalhes.classList.add('hidden');
        }
    }

    // Prepara a confirmação para o modal
    window.prepararConfirmacaoDistribuicao = (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const lacresSelecionados = formData.getAll('lacres_a_substituir');
        const msgErro = document.getElementById('mensagemErroDistribuir');

        if (lacresSelecionados.length === 0) {
            msgErro.textContent = 'Selecione pelo menos um lacre para distribuir.';
            msgErro.style.display = 'block';
            return;
        }

        lacresParaConfirmar = { id_equipamento: formData.get('id_equipamento'), lacres: [] };
        let temNumeroVazio = false;

        lacresSelecionados.forEach(local => {
            const numero = formData.get(`num_${local}`);
            if (!numero || numero.trim() === '') temNumeroVazio = true;
            lacresParaConfirmar.lacres.push({
                local: local,
                numero: numero,
                obs: formData.get(`obs_${local}`) || ''
            });
        });

        if (temNumeroVazio) {
            msgErro.textContent = 'Preencha o número para todos os lacres selecionados.';
            msgErro.style.display = 'block';
            return;
        }
        msgErro.style.display = 'none';

        // Usa o modal de confirmação genérico
        operacaoAtual = 'distribute';
        const resumoContainer = document.getElementById('resumoLacres');
        resumoContainer.innerHTML = lacresParaConfirmar.lacres.map(l =>
            `<p><strong>${l.local}:</strong> ${l.numero}${l.obs ? `<br><small><em>Obs: ${l.obs}</em></small>` : ''}</p>`
        ).join('');

        document.getElementById('tituloConfirmacao').textContent = 'Confirmar Lacres a Distribuir';
        fecharModal('modalDistribuirRompido');
        abrirModal('modalConfirmacao');
    };


    window.abrirModalEdicaoIndividual = (lacre) => {
        const form = document.getElementById('formEditarLacreIndividual');
        form.reset();

        // Resetar estado do modal
        document.getElementById('mensagemSalvarIndividual').style.display = 'none';
        document.getElementById('botoesEdicaoIndividual').style.display = 'flex';
        form.querySelector('button[type="submit"]').disabled = false;
        form.querySelector('.spinner').classList.add('hidden');

        // Preenche o ID e o TÍTULO
        form.querySelector('[name="id_controle_lacres"]').value = lacre.id_controle_lacres;
        document.getElementById('localLacreIndividualTitulo').textContent = lacre.local_lacre;

        // Mapeia os elementos do formulário
        const campoDataFixacao = document.getElementById('campoDataFixacaoIndividual');
        const campoDataRompimento = document.getElementById('campoDataRompimentoIndividual');
        const campoDataPsie = document.getElementById('campoDataPsieIndividual');
        const campoObs = document.getElementById('campoObsIndividual'); // <-- Mapeia o container da observação
        const inputNumero = form.querySelector('[name="numero_lacre"]');
        const inputObs = form.querySelector('[name="obs_lacre"]');
        const inputDataFixacao = form.querySelector('[name="dt_fixacao"]');
        const inputDataRompimento = form.querySelector('[name="dt_rompimento"]');
        const inputDataPsie = form.querySelector('[name="dt_reporta_psie"]');

        // Esconde todos os campos opcionais por padrão
        campoDataFixacao.classList.add('hidden');
        campoDataRompimento.classList.add('hidden');
        campoDataPsie.classList.add('hidden');
        campoObs.classList.add('hidden'); // <-- ALTERAÇÃO AQUI: Esconde a observação por padrão

        if (lacre.lacre_afixado == 1) {
            inputNumero.value = lacre.num_lacre || '';
            inputDataFixacao.value = lacre.dt_fixacao || '';
            campoDataFixacao.classList.remove('hidden');
            // O campo de observação permanece oculto
        } else if (lacre.lacre_rompido == 1) {
            inputNumero.value = lacre.num_lacre_rompido || '';
            inputDataRompimento.value = lacre.dt_rompimento || '';
            campoDataRompimento.classList.remove('hidden');

            // Mostra a observação APENAS para lacres rompidos
            campoObs.classList.remove('hidden');
            inputObs.value = lacre.obs_lacre || '';

            if (lacre.dt_reporta_psie) {
                inputDataPsie.value = lacre.dt_reporta_psie;
                campoDataPsie.classList.remove('hidden');
            }
        } else { // Distribuído
            inputNumero.value = lacre.num_lacre_distribuido || '';
            // O campo de observação permanece oculto
        }

        abrirModal('modalEditarLacreIndividual');
    };


    window.salvarEdicaoIndividual = async (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const btnSubmit = form.querySelector('button[type="submit"]');
        const spinner = btnSubmit.querySelector('.spinner');
        const msgDiv = document.getElementById('mensagemSalvarIndividual');

        btnSubmit.disabled = true;
        spinner.classList.remove('hidden');
        msgDiv.style.display = 'none';

        try {
            const response = await fetch('API/editar_lacre_unico.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (!result.success) { throw new Error(result.message); }

            msgDiv.className = 'mensagem sucesso';
            msgDiv.textContent = result.message;
            msgDiv.style.display = 'block';
            document.getElementById('botoesEdicaoIndividual').style.display = 'none';

            setTimeout(() => {
                fecharModal('modalEditarLacreIndividual');
                buscarEquipamentosELacres(); // Atualiza a lista principal
            }, 1500);

        } catch (error) {
            msgDiv.className = 'mensagem erro';
            msgDiv.textContent = `Erro: ${error.message}`;
            msgDiv.style.display = 'block';
            btnSubmit.disabled = false;
            spinner.classList.add('hidden');
        }
    };

    carregarDadosIniciais();
});