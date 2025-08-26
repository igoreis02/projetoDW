document.addEventListener('DOMContentLoaded', () => {
    // Variáveis globais de estado
    let todosOsEquipamentos = [];
    let equipamentosFiltrados = [];
    let lacresParaConfirmar = {};

    const campoPesquisa = document.getElementById('campoPesquisa');
    campoPesquisa.addEventListener('input', () => buscarEExibirLacres(campoPesquisa.value));

    // Funções de Modal
    window.abrirModal = (id) => document.getElementById(id).classList.add('esta-ativo');
    window.fecharModal = (id) => document.getElementById(id).classList.remove('esta-ativo');

    // Carregamento inicial
    async function buscarEExibirLacres(termoPesquisa = '') {
        const container = document.getElementById('containerListaLacres');
        container.innerHTML = 'Carregando...';

        try {
            const response = await fetch(`API/get_equipamentos_lacres.php?search=${termoPesquisa}`);
            const data = await response.json();
            
            if (data.success) {
                todosOsEquipamentos = data.equipamentos;
                renderizarLista(todosOsEquipamentos);
            } else {
                container.innerHTML = `<p class="mensagem erro">${data.message}</p>`;
            }
        } catch (error) {
            container.innerHTML = `<p class="mensagem erro">Erro de conexão ao buscar dados.</p>`;
        }
    }

    function renderizarLista(equipamentos) {
        const container = document.getElementById('containerListaLacres');
        container.innerHTML = '';

        if (equipamentos.length === 0) {
            container.innerHTML = '<p class="mensagem">Nenhum equipamento encontrado.</p>';
            return;
        }

        equipamentos.forEach(equip => {
            const temLacres = equip.lacres && equip.lacres.length > 0;
            const lacresHTML = temLacres
                ? equip.lacres.map(lacre => `<div class="lacre-item"><strong>${lacre.local_lacre}:</strong> ${lacre.num_lacre}</div>`).join('')
                : '<p>Nenhum lacre cadastrado.</p>';

            const itemHTML = `
                <div class="item-equipamento-lacre">
                    <div class="equipamento-info">
                        <div class="info-bloco">
                            <h4>Equipamento</h4>
                            <p>${equip.nome_equip} - ${equip.referencia_equip}</p>
                        </div>
                        <div class="info-bloco">
                            <h4>Detalhes</h4>
                            <p><strong>Qtd. Faixas:</strong> ${equip.qtd_faixa || 'N/A'}</p>
                            <p><strong>KM via:</strong> ${equip.km || 'N/A'}</p>
                        </div>
                        <div class="info-bloco">
                             <h4>Aferição</h4>
                             <p><strong>N° Instrumento:</strong> ${equip.num_instrumento || 'N/A'}</p>
                             <p><strong>Data:</strong> ${equip.dt_afericao ? new Date(equip.dt_afericao + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A'}</p>
                             <p><strong>Vencimento:</strong> ${equip.dt_vencimento ? new Date(equip.dt_vencimento + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A'}</p>
                        </div>
                    </div>
                    <h4>Lacres</h4>
                    <div class="lacres-grid">${lacresHTML}</div>
                </div>
            `;
            container.innerHTML += itemHTML;
        });
    }

    // Fluxo de Adicionar Lacres
    window.abrirModalCidades = async () => {
        const lista = document.getElementById('listaCidades');
        lista.innerHTML = 'Carregando...';
        abrirModal('modalCidades');
        try {
            const res = await fetch('API/get_cidades.php');
            const data = await res.json();
            if (data.success) {
                lista.innerHTML = '';
                data.cidades.forEach(cidade => {
                    const btn = document.createElement('button');
                    btn.textContent = cidade.nome;
                    btn.onclick = () => selecionarCidade(cidade.id_cidade);
                    lista.appendChild(btn);
                });
            } else {
                lista.innerHTML = `<p class="mensagem erro">${data.message}</p>`;
            }
        } catch (error) {
            lista.innerHTML = `<p class="mensagem erro">Erro ao carregar cidades.</p>`;
        }
    };

    window.selecionarCidade = async (idCidade) => {
        const lista = document.getElementById('listaEquipamentos');
        const pesquisaInput = document.getElementById('pesquisaEquipamento');
        pesquisaInput.value = '';
        lista.innerHTML = 'Carregando...';
        fecharModal('modalCidades');
        abrirModal('modalEquipamentos');

        try {
            const res = await fetch(`API/get_equipamentos.php?city_id=${idCidade}`);
            const data = await res.json();
            if (data.success) {
                // *** ALTERAÇÃO AQUI: Filtra os equipamentos antes de exibir ***
                equipamentosFiltrados = data.equipamentos.filter(equip => equip.tipo_equip !== 'CCO' && equip.tipo_equip !== 'DOME');
                renderizarEquipamentosParaSelecao(equipamentosFiltrados);
            } else {
                lista.innerHTML = `<p class="mensagem erro">${data.message}</p>`;
            }
        } catch (error) {
            lista.innerHTML = `<p class="mensagem erro">Erro ao carregar equipamentos.</p>`;
        }

        pesquisaInput.oninput = () => {
            const termo = pesquisaInput.value.toLowerCase();
            const filtrados = equipamentosFiltrados.filter(e => 
                e.nome_equip.toLowerCase().includes(termo) || 
                (e.referencia_equip && e.referencia_equip.toLowerCase().includes(termo))
            );
            renderizarEquipamentosParaSelecao(filtrados);
        };
    };
    
    function renderizarEquipamentosParaSelecao(equipamentos) {
        const lista = document.getElementById('listaEquipamentos');
        lista.innerHTML = '';
        if (equipamentos.length === 0) {
            lista.innerHTML = '<p>Nenhum equipamento que necessite de lacre foi encontrado para esta cidade.</p>';
            return;
        }
        equipamentos.forEach(equip => {
            const btn = document.createElement('button');
            btn.textContent = `${equip.nome_equip} - ${equip.referencia_equip}`;
            btn.onclick = () => selecionarEquipamento(equip);
            lista.appendChild(btn);
        });
    }

    window.selecionarEquipamento = (equipamento) => {
        document.getElementById('formularioAdicionarLacres').reset();
        document.getElementById('idEquipamentoLacre').value = equipamento.id_equipamento;
        document.getElementById('tituloModalLacres').textContent = `Adicionar Lacres para: ${equipamento.nome_equip}`;
        toggleCameras(1, document.querySelector('.botoes-toggle button[data-cameras="1"]'));
        fecharModal('modalEquipamentos');
        abrirModal('modalAdicionarLacres');
    };

    window.toggleCameras = (num, btnElement) => {
        document.querySelectorAll('.botoes-toggle button').forEach(btn => btn.classList.remove('ativo'));
        btnElement.classList.add('ativo');

        const unica = document.getElementById('cameraFxUnica');
        const dupla = document.getElementById('cameraFxDupla');
        const inputA = document.getElementById('lacreCameraFxA');
        const inputB = document.getElementById('lacreCameraFxB');
        const inputAB = document.getElementById('lacreCameraFxAB');

        if (num === 1) {
            unica.classList.remove('hidden');
            dupla.classList.add('hidden');
            inputA.value = '';
            inputB.value = '';
        } else {
            unica.classList.add('hidden');
            dupla.classList.remove('hidden');
            inputAB.value = '';
        }
    };

    document.getElementById('formularioAdicionarLacres').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        lacresParaConfirmar = {
            id_equipamento: formData.get('id_equipamento'),
            lacres: []
        };
        const resumo = document.getElementById('resumoLacres');
        resumo.innerHTML = '';
        
        for (const [key, value] of formData.entries()) {
            if (key !== 'id_equipamento' && value.trim() !== '') {
                lacresParaConfirmar.lacres.push({ local: key, numero: value });
                resumo.innerHTML += `<p><strong>${key.charAt(0).toUpperCase() + key.slice(1)}:</strong> ${value}</p>`;
            }
        }

        if (lacresParaConfirmar.lacres.length > 0) {
            fecharModal('modalAdicionarLacres');
            abrirModal('modalConfirmacao');
        } else {
            alert('Preencha pelo menos um campo de lacre.');
        }
    });

    window.enviarLacres = async () => {
        const msgDiv = document.getElementById('mensagemSalvar');
        msgDiv.style.display = 'none';
        
        try {
            const response = await fetch('API/add_lacres.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(lacresParaConfirmar)
            });
            const result = await response.json();
            if (result.success) {
                msgDiv.className = 'mensagem sucesso';
                msgDiv.textContent = 'Lacres salvos com sucesso!';
                msgDiv.style.display = 'block';
                setTimeout(() => {
                    fecharModal('modalConfirmacao');
                    buscarEExibirLacres();
                }, 2000);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            msgDiv.className = 'mensagem erro';
            msgDiv.textContent = `Erro: ${error.message}`;
            msgDiv.style.display = 'block';
        }
    };

    // Iniciar
    buscarEExibirLacres();
});