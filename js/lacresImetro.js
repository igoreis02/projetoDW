document.addEventListener('DOMContentLoaded', () => {
    let todosOsEquipamentos = [];
    let lacresParaConfirmar = {};
    let operacaoAtual = '';
    let filtroCidadeAtivo = 'Todas';

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

    window.abrirModal = (id) => document.getElementById(id).classList.add('esta-ativo');
    window.fecharModal = (id) => document.getElementById(id).classList.remove('esta-ativo');

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

    function showNotifications(expiringItems) {
        const container = document.getElementById('notification-container');
        let delay = 0;
        expiringItems.forEach(item => {
            setTimeout(() => {
                const notification = document.createElement('div');
                notification.className = 'notification';
                const vencimento = new Date(item.dt_vencimento + 'T00:00:00').toLocaleDateString('pt-BR');
                notification.innerHTML = `<p><strong>Vencimento Próximo!</strong></p><p>${item.nome_equip} - ${item.referencia_equip}</p><p>${item.cidade_nome} | Vence em: ${vencimento}</p>`;
                container.appendChild(notification);
                setTimeout(() => notification.classList.add('show'), 10);
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 500);
                }, 5000);
            }, delay);
            delay += 5500;
        });
    }

    async function carregarDadosIniciais() {
        await carregarFiltrosDeCidade();
        await buscarEquipamentosELacres(true);
    }

    async function carregarFiltrosDeCidade() {
        try {
            const res = await fetch('API/get_cidades.php');
            const data = await res.json();
            if (data.success) {
                containerFiltroCidades.innerHTML = '';
                const btnTodas = document.createElement('button');
                btnTodas.textContent = 'Todas';
                btnTodas.className = 'ativo';
                btnTodas.onclick = () => setFiltroCidade('Todas');
                containerFiltroCidades.appendChild(btnTodas);
                data.cidades.forEach(cidade => {
                    const btn = document.createElement('button');
                    btn.textContent = cidade.nome;
                    btn.onclick = () => setFiltroCidade(cidade.nome);
                    containerFiltroCidades.appendChild(btn);
                });
            }
        } catch (error) {
            containerFiltroCidades.innerHTML = 'Erro ao carregar cidades.';
        }
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
                    const expiring = getExpiringItems(todosOsEquipamentos);
                    showNotifications(expiring);
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

    function renderizarLista() {
        containerListaLacres.innerHTML = '';
        const termoPesquisa = campoPesquisa.value.toLowerCase();

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
                const temLacres = equip.lacres && equip.lacres.length > 0;
                
                const lacresHTML = temLacres ? equip.lacres.map(lacre => {
                    const formName = dbValueToFormName[lacre.local_lacre];
                    // CORREÇÃO: Trata nomes inválidos ou não mapeados de forma elegante
                    const displayName = formName ? LacreMap[formName].displayName : (lacre.local_lacre || 'Lacre Inválido');
                    return `<div class="lacre-item"><strong>${displayName}:</strong> ${lacre.num_lacre}</div>`;
                }).join('') : '<p>Nenhum lacre cadastrado.</p>';
                
                const lacresData = JSON.stringify(equip.lacres).replace(/"/g, '&quot;');
                const vencimentoTexto = equip.dt_vencimento ? new Date(equip.dt_vencimento + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A';

                const itemHTML = `<div class="${itemClass}"><div class="equipamento-info"><div class="info-bloco"><h4>Equipamento</h4><p class="equipamento-identificacao">${equip.nome_equip} - ${equip.referencia_equip}</p></div><div class="info-bloco"><h4>Detalhes</h4><p><strong>Qtd. Faixas:</strong> ${equip.qtd_faixa || 'N/A'}</p><p><strong>KM via:</strong> ${equip.km || 'N/A'}</p></div><div class="info-bloco"><h4>Aferição</h4><p><strong>N° Instrumento:</strong> ${equip.num_instrumento || 'N/A'}</p><p><strong>Data:</strong> ${equip.dt_afericao ? new Date(equip.dt_afericao + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A'}</p><p class="${vencimentoClass}"><strong>Vencimento:</strong> ${vencimentoTexto}</p></div></div><h4>Lacres</h4><div class="lacres-grid">${lacresHTML}</div><div class="equipamento-actions"><button class="botao-adicionar-lacres" data-equip-id="${equip.id_equipamento}" data-equip-name="${equip.nome_equip}" data-lacres="${lacresData}" onclick="abrirModalLacres(this)">${temLacres ? 'Substituir Lacres' : 'Adicionar Lacres'}</button></div></div>`;
                grupoDiv.innerHTML += itemHTML;
            });
            containerListaLacres.appendChild(grupoDiv);
        }
    }
    
    // --- LÓGICA DE ABRIR MODAL COMPLETAMENTE REFEITA E CORRIGIDA ---
    window.abrirModalLacres = (btn) => {
        const form = document.getElementById('formularioAdicionarLacres');
        form.reset();
        
        const lacresAtuais = btn.dataset.lacres ? JSON.parse(btn.dataset.lacres) : [];
        const temLacres = lacresAtuais.length > 0;
        operacaoAtual = temLacres ? 'substitute' : 'add';

        form.querySelector('[name="id_equipamento"]').value = btn.dataset.equipId;
        document.getElementById('tituloModalAdicionarLacres').textContent = `${temLacres ? 'Substituir' : 'Adicionar'} Lacres para: ${btn.dataset.equipName}`;
        
        if (!temLacres) {
            // Se for ADICIONAR, apenas reseta os toggles para o padrão e abre o modal
            toggleCameras(1, form.querySelector('.zoom-toggle button'), 'zoom');
            toggleCameras(1, form.querySelector('.pam-toggle button'), 'pam');
            abrirModal('modalAdicionarLacres');
            return;
        }

        // Se for SUBSTITUIR, executa a lógica de preenchimento
        // Cria um mapa simples com os lacres existentes para facilitar a busca
        const lacresMap = new Map(lacresAtuais.map(l => [l.local_lacre.toLowerCase(), l.num_lacre]));

        // Lógica para Câmera ZOOM
        const zoomA = lacresMap.get('camera zoom (fx. a)');
        const zoomB = lacresMap.get('camera zoom (fx. b)');
        const zoomAB = lacresMap.get('camera zoom (fx. a/b)');
        if (zoomA || zoomB) {
            toggleCameras(2, form.querySelector('.zoom-toggle button'), 'zoom');
            if (zoomA) form.querySelector('[name="camera_zoom_a"]').value = zoomA;
            if (zoomB) form.querySelector('[name="camera_zoom_b"]').value = zoomB;
        } else {
            toggleCameras(1, form.querySelector('.zoom-toggle button'), 'zoom');
            if (zoomAB) form.querySelector('[name="camera_zoom_ab"]').value = zoomAB;
        }

        // Lógica para Câmera PAM
        const pamA = lacresMap.get('camera pam (fx. a)');
        const pamB = lacresMap.get('camera pam (fx. b)');
        const pamAB = lacresMap.get('camera pam (fx. a/b)') || lacresMap.get('camera pam'); // Compatibilidade com dado antigo
        if (pamA || pamB) {
            toggleCameras(2, form.querySelector('.pam-toggle button'), 'pam');
            if (pamA) form.querySelector('[name="camera_pam_a"]').value = pamA;
            if (pamB) form.querySelector('[name="camera_pam_b"]').value = pamB;
        } else {
            toggleCameras(1, form.querySelector('.pam-toggle button'), 'pam');
            if (pamAB) form.querySelector('[name="camera_pam_ab"]').value = pamAB;
        }

        // Lógica para outros lacres
        const outrosLacres = {
            'metrologico': 'metrologico',
            'nao metrologico': 'nao_metrologico',
            'fonte': 'fonte',
            'switch': 'switch_lacre'
        };
        for (const [dbName, formName] of Object.entries(outrosLacres)) {
            if (lacresMap.has(dbName)) {
                const input = form.querySelector(`[name="${formName}"]`);
                if(input) input.value = lacresMap.get(dbName);
            }
        }

        abrirModal('modalAdicionarLacres');
    };

    window.prepararEnvio = (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        lacresParaConfirmar = { id_equipamento: formData.get('id_equipamento'), lacres: [] };
        const resumo = document.getElementById('resumoLacres');
        resumo.innerHTML = '';
        let temLacre = false;
        formData.forEach((value, formName) => {
            const lacreInfo = LacreMap[formName];
            if (lacreInfo && value.trim() !== '') {
                temLacre = true;
                lacresParaConfirmar.lacres.push({ local: lacreInfo.dbValue, numero: value });
                resumo.innerHTML += `<p><strong>${lacreInfo.displayName}:</strong> ${value}</p>`;
            }
        });
        if (temLacre) {
            document.getElementById('tituloConfirmacao').textContent = (operacaoAtual === 'substitute' ? 'Confirmar Substituição' : 'Confirmar Novos Lacres');
            fecharModal('modalAdicionarLacres');
            abrirModal('modalConfirmacao');
        } else {
            alert('Preencha pelo menos um campo de lacre.');
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

    dataFim.addEventListener('change', () => {
        if (dataFim.value && dataInicio.value && dataFim.value < dataInicio.value) {
            alert('A data final não pode ser anterior à data inicial.');
            dataFim.value = '';
        }
    });

    document.getElementById('btnConfirmarSalvar').onclick = async () => {
        const endpoint = operacaoAtual === 'substitute' ? 'API/update_lacres.php' : 'API/add_lacres.php';
        const msgDiv = document.getElementById('mensagemSalvar');
        msgDiv.style.display = 'none';
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(lacresParaConfirmar)
            });
            const result = await response.json();
            if (result.success) {
                msgDiv.className = 'mensagem sucesso';
                msgDiv.textContent = result.message;
                msgDiv.style.display = 'block';
                setTimeout(() => {
                    fecharModal('modalConfirmacao');
                    buscarEquipamentosELacres();
                }, 2000);
            } else { throw new Error(result.message); }
        } catch (error) {
            msgDiv.className = 'mensagem erro';
            msgDiv.textContent = `Erro: ${error.message}`;
            msgDiv.style.display = 'block';
        }
    };

    carregarDadosIniciais();
});