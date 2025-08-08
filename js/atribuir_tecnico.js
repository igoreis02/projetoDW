// Obtém os elementos do modal
const modalCidades = document.getElementById('modalCidades');
const modalTitulo = document.getElementById('modalTitulo');
const listaCidades = document.getElementById('listaCidades');
const botaoVoltar = document.getElementById('botaoVoltar');
const botaoAtribuirTecnico = document.getElementById('botaoAtribuirTecnico');
let manutencoesPendentes = [];
let isProcessing = false;

// Adiciona os eventos de clique
botaoVoltar.addEventListener('click', abrirModalCidades);
botaoAtribuirTecnico.addEventListener('click', atribuirTecnico);

// Função para abrir o modal e buscar as cidades (estado inicial)
function abrirModalCidades() {
    // Referência aos elementos de mensagem
    const mensagemErro = document.getElementById('mensagem-erro');
    const mensagemSucesso = document.getElementById('mensagem-sucesso');

    // Oculta ambas as mensagens ao iniciar, garantindo um estado limpo
    if (mensagemErro) {
        mensagemErro.style.display = 'none';
    }
    if (mensagemSucesso) {
        mensagemSucesso.style.display = 'none';
    }

    modalCidades.style.display = 'block';

    modalTitulo.textContent = "Cidades Cadastradas";
    listaCidades.innerHTML = '';
    botaoVoltar.style.display = 'none';
    botaoAtribuirTecnico.style.display = 'none';

    fetch('get_cidades.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.cidades && data.cidades.length > 0) {
                data.cidades.forEach(cidade => {
                    const botao = document.createElement('button');
                    botao.classList.add('botao-cidade');
                    botao.textContent = cidade.nome;
                    botao.setAttribute('data-id', cidade.id_cidade);
                    botao.addEventListener('click', () => {
                        buscarManutencoesPendentes(cidade.id_cidade, cidade.nome);
                    });
                    listaCidades.appendChild(botao);
                });
            } else {
                listaCidades.innerHTML = '<p>Nenhuma cidade encontrada.</p>';
            }
            modalCidades.style.display = 'block';
        })
        .catch(error => {
            console.error('Erro ao carregar as cidades:', error);
            listaCidades.innerHTML = `<p class="erro">Erro ao carregar cidades.</p>`;
            modalCidades.style.display = 'block';
        });
}

// Função para buscar as manutenções pendentes
function buscarManutencoesPendentes(id_cidade, nome_cidade) {
    const flow_type = 'maintenance';
    
    fetch(`get_atribuicoes_pendentes.php?city_id=${id_cidade}&flow_type=${flow_type}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            manutencoesPendentes = data.items;
            if (data.success && manutencoesPendentes && manutencoesPendentes.length > 0) {
                exibirManutencoes(manutencoesPendentes, nome_cidade);
            } else {
                modalTitulo.textContent = `Manutenções Pendentes em ${nome_cidade}`;
                listaCidades.innerHTML = `<p>Nenhuma manutenção pendente encontrada para esta cidade.</p>`;
                botaoVoltar.style.display = 'block';
                botaoAtribuirTecnico.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar manutenções:', error);
            modalTitulo.textContent = `Erro em ${nome_cidade}`;
            listaCidades.innerHTML = `<p class="erro">Erro ao carregar as manutenções.</p>`;
            botaoVoltar.style.display = 'block';
            botaoAtribuirTecnico.style.display = 'none';
        });
}

// Função para exibir as manutenções no modal e adicionar a lógica de seleção
function exibirManutencoes(manutencoes, nome_cidade) {
    modalTitulo.textContent = `Manutenções Pendentes em ${nome_cidade}`;
    listaCidades.innerHTML = '';
    botaoVoltar.style.display = 'block';
    botaoAtribuirTecnico.style.display = 'none';

    manutencoes.forEach(manutencao => {
        const botao = document.createElement('button');
        botao.classList.add('botao-manutencao-pendente');
        botao.setAttribute('data-id', manutencao.id_manutencao);

        const textoBotao = `${manutencao.nome_equip} - ${manutencao.referencia_equip}\nOcorrência: ${manutencao.ocorrencia_reparo}`;
        botao.textContent = textoBotao;
        
        botao.addEventListener('click', () => {
            botao.classList.toggle('selecionado');
            verificarSelecao();
        });

        listaCidades.appendChild(botao);
    });
}

// Função para verificar se há itens selecionados e mostrar/esconder o botão de atribuição
function verificarSelecao() {
    const itensSelecionados = document.querySelectorAll('.botao-manutencao-pendente.selecionado');
    if (itensSelecionados.length > 0) {
        botaoAtribuirTecnico.style.display = 'block';
    } else {
        botaoAtribuirTecnico.style.display = 'none';
    }
}

// Função para lidar com o clique no botão de atribuir técnico
function atribuirTecnico() {
     const mensagemErro = document.getElementById('mensagem-erro');
    if (mensagemErro) {
        mensagemErro.style.display = 'none'; // Oculta a mensagem de erro
    }
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

            const tituloEquipamento = document.createElement('p');
            tituloEquipamento.classList.add('titulo-equipamento');
            tituloEquipamento.innerHTML = `<strong>${manutencao.referencia_equip} - ${manutencao.nome_equip}</strong>`;

            const ocorrenciaReparo = document.createElement('p');
            ocorrenciaReparo.classList.add('descricao-problema');
            ocorrenciaReparo.textContent = `DESCRIÇÃO PROBLEMA: ${manutencao.ocorrencia_reparo}`;
            
            divItem.appendChild(tituloEquipamento);
            divItem.appendChild(ocorrenciaReparo);
            
            manutencoesContainer.appendChild(divItem);
        });

        const tituloData = document.createElement('h3');
        tituloData.textContent = 'Selecione a data para execução';
        tituloData.classList.add('titulo-data');
        listaCidades.appendChild(tituloData);

        const dataContainer = document.createElement('div');
        dataContainer.classList.add('data-container');

        const inicioDiv = document.createElement('div');
        const labelInicio = document.createElement('label');
        labelInicio.textContent = 'Data de Início:';
        labelInicio.htmlFor = 'dataInicio';
        const inputInicio = document.createElement('input');
        inputInicio.type = 'date';
        inputInicio.id = 'dataInicio';
        inputInicio.classList.add('input-data');
        inicioDiv.appendChild(labelInicio);
        inicioDiv.appendChild(inputInicio);
        dataContainer.appendChild(inicioDiv);

        const fimDiv = document.createElement('div');
        const labelFim = document.createElement('label');
        labelFim.textContent = 'Data de Fim:';
        labelFim.htmlFor = 'dataFim';
        const inputFim = document.createElement('input');
        inputFim.type = 'date';
        inputFim.id = 'dataFim';
        inputFim.classList.add('input-data');
        fimDiv.appendChild(labelFim);
        fimDiv.appendChild(inputFim);
        dataContainer.appendChild(fimDiv);

        listaCidades.appendChild(dataContainer);

        const tituloTecnicos = document.createElement('h3');
        tituloTecnicos.textContent = 'Selecione o(s) Técnico(s)';
        tituloTecnicos.classList.add('titulo-tecnicos');
        listaCidades.appendChild(tituloTecnicos);

        const tecnicosContainer = document.createElement('div');
        tecnicosContainer.classList.add('tecnicos-container');
        listaCidades.appendChild(tecnicosContainer);

        fetch('get_tecnicos.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao buscar técnicos: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.tecnicos && data.tecnicos.length > 0) {
                    data.tecnicos.forEach(tecnico => {
                        const botaoTecnico = document.createElement('button');
                        botaoTecnico.classList.add('botao-tecnico');
                        botaoTecnico.textContent = tecnico.nome;
                        botaoTecnico.setAttribute('data-id', tecnico.id_tecnico);
                        
                        botaoTecnico.addEventListener('click', () => {
                            botaoTecnico.classList.toggle('selecionado-tecnico');
                        });
                        tecnicosContainer.appendChild(botaoTecnico);
                    });
                } else {
                    tecnicosContainer.innerHTML = `<p>Nenhum técnico encontrado.</p>`;
                }
            })
            .catch(error => {
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
        
        fetch('get_veiculos.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao buscar veículos: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.length > 0) {
                    data.forEach(veiculo => {
                        const botaoVeiculo = document.createElement('button');
                        botaoVeiculo.classList.add('botao-veiculo');
                        botaoVeiculo.innerHTML = `${veiculo.nome}<br>${veiculo.placa}`;
                        botaoVeiculo.setAttribute('data-id', veiculo.id_veiculo);
                        
                        botaoVeiculo.addEventListener('click', () => {
                            botaoVeiculo.classList.toggle('selecionado-veiculo');
                        });
                        veiculosContainer.appendChild(botaoVeiculo);
                    });
                } else {
                    veiculosContainer.innerHTML = `<p>Nenhum veículo encontrado.</p>`;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar veículos:', error);
                veiculosContainer.innerHTML = `<p class="erro">Erro ao carregar veículos.</p>`;
            });
        
        const botaoContainer = document.createElement('div');
        botaoContainer.classList.add('botao-container');

        const novoBotao = document.createElement('button');
        novoBotao.id = 'botaoAtribuirManutencao'; 
        novoBotao.classList.add('botao-selecionar-tecnicos');
        novoBotao.textContent = 'Atribuir Manutenção';
        
        novoBotao.addEventListener('click', atribuirManutencaoCompleto);
        
        botaoContainer.appendChild(novoBotao);
        
        listaCidades.appendChild(botaoContainer);

    } else {
        listaCidades.innerHTML = `<p>Nenhuma manutenção selecionada para atribuição.</p>`;
    }

    isProcessing = false;
}

// --- FUNÇÃO CORRIGIDA: Envia os dados para o servidor e valida a data ---
function atribuirManutencaoCompleto() {
    const botaoAtribuir = document.getElementById('botaoAtribuirManutencao');
    const mensagemErro = document.getElementById('mensagem-erro');
    const mensagemSucesso = document.getElementById('mensagem-sucesso');

    if (!botaoAtribuir || !mensagemErro || !mensagemSucesso) {
        console.error('Erro: Elementos HTML não encontrados. Verifique seu HTML.');
        return;
    }

    mensagemErro.style.display = 'none';
    mensagemSucesso.style.display = 'none';

    botaoAtribuir.disabled = true;
    botaoAtribuir.textContent = 'Carregando...';

    const idsManutencao = Array.from(document.querySelectorAll('.manutencoes-flex-container .detalhe-manutencao'))
                               .map(item => parseInt(item.getAttribute('data-id'), 10))
                               .filter(id => !isNaN(id) && id !== null);

    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;

    const idsTecnicos = Array.from(document.querySelectorAll('.botao-tecnico.selecionado-tecnico'))
                               .map(item => parseInt(item.getAttribute('data-id'), 10))
                               .filter(id => !isNaN(id) && id !== null);

    const idsVeiculos = Array.from(document.querySelectorAll('.botao-veiculo.selecionado-veiculo'))
                               .map(item => parseInt(item.getAttribute('data-id'), 10))
                               .filter(id => !isNaN(id) && id !== null);
    
    const camposFaltantes = [];

    if (idsManutencao.length === 0) {
        camposFaltantes.push('Manutenções');
    }
    if (!dataInicio) {
        camposFaltantes.push('Data de Início');
    }
    if (!dataFim) {
        camposFaltantes.push('Data de Fim');
    }
    if (idsTecnicos.length === 0) {
        camposFaltantes.push('Técnicos');
    }
    if (idsVeiculos.length === 0) {
        camposFaltantes.push('Veículos');
    }

    // NOVO: Adiciona a validação para a data de início
    if (dataInicio) {
        const dataAtual = new Date();
        const dataInicioObj = new Date(dataInicio + 'T00:00:00'); // Adiciona T00:00:00 para evitar problemas de fuso horário
        
        // Zera as horas, minutos, segundos e milissegundos da data atual para comparar apenas a data
        dataAtual.setHours(0, 0, 0, 0);
        
        // A condição agora é 'menor que', permitindo que a data seja igual a hoje
        if (dataInicioObj < dataAtual) {
            camposFaltantes.push('A data de Início não pode ser anterior à data atual.');
        }
    }


    if (camposFaltantes.length > 0) {
        mensagemErro.textContent = `Por favor, selecione ou preencha o(s) seguinte(s) campo(s): ${camposFaltantes.join(', ')}.`;
        mensagemErro.style.display = 'block';
        botaoAtribuir.disabled = false;
        botaoAtribuir.textContent = 'Atribuir Manutenção';
        return;
    }

    const dataToSend = {
        idsManutencao,
        dataInicio,
        dataFim,
        idsTecnicos,
        idsVeiculos
    };

    fetch('atribuir_tecnicos_manutencao.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dataToSend)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na resposta do servidor.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            botaoAtribuir.style.display = 'none';
            mensagemSucesso.textContent = 'Técnico(s) atribuído(s) com sucesso!';
            mensagemSucesso.style.display = 'block';

            setTimeout(() => {
                fecharModalCidades();
                abrirModalCidades();
            }, 3000);
        } else {
            alert('Erro ao atribuir técnico: ' + data.message);
            botaoAtribuir.disabled = false;
            botaoAtribuir.textContent = 'Atribuir Manutenção';
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        alert('Erro ao se comunicar com o servidor. Tente novamente.');
        botaoAtribuir.disabled = false;
        botaoAtribuir.textContent = 'Atribuir Manutenção';
    });
}


// Função para fechar o modal
function fecharModalCidades() {
    modalCidades.style.display = 'none';
}

// Fechar o modal clicando fora dele
window.onclick = function(event) {
    if (event.target === modalCidades) {
        fecharModalCidades();
    }
}