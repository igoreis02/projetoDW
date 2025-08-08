// Referências para elementos da DOM (adicionar o novo input)
const listaManutencoes = document.getElementById('listaManutencoes');
const mensagemCarregamento = document.getElementById('mensagemCarregamento');
const mensagemErro = document.getElementById('mensagemErro');

// Referências para o modal de conclusão de reparo
const modalConcluirReparo = document.getElementById('modalConcluirReparo');
const nomeEquipamentoModal = document.getElementById('nomeEquipamentoModal');
const referenciaEquipamentoModal = document.getElementById('referenciaEquipamentoModal');
const ocorrenciaReparoModal = document.getElementById('ocorrenciaReparoModal');
const inputMateriaisUtilizados = document.getElementById('inputMateriaisUtilizados');
const textareaReparoRealizado = document.getElementById('textareaReparoRealizado');
const botaoConfirmarConcluirReparo = document.getElementById('botaoConfirmarConcluirReparo');
const spinnerConcluirReparo = document.getElementById('spinnerConcluirReparo');
const mensagemConcluirReparo = document.getElementById('mensagemConcluirReparo');

let idManutencaoAtualParaConcluir = null;

// Funções de utilidade
function mostrarMensagem(elemento, msg, tipo) {
    elemento.textContent = msg;
    elemento.className = `mensagem ${tipo}`;
    elemento.classList.remove('oculto');
}

function ocultarMensagem(elemento) {
    elemento.classList.add('oculto');
    elemento.textContent = '';
}

function alternarSpinner(botao, spinner, mostrar) {
    if (mostrar) {
        spinner.classList.remove('oculto');
        botao.disabled = true;
    } else {
        spinner.classList.add('oculto');
        botao.disabled = false;
    }
}

function calcularDiasEntreDatas(inicioReparoTec, fimReparoT) {
    if (!inicioReparoTec || !fimReparoT) return 0;
    
    const inicio = new Date(inicioReparoTec);
    const fim = new Date(fimReparoT);
    
    const diferencaTempo = fim - inicio;
    const diferencaDias = Math.ceil(diferencaTempo / (1000 * 60 * 60 * 24));
    
    return diferencaDias >= 0 ? diferencaDias : 0;
}

// Função para abrir o modal de conclusão de reparo
function abrirModalConcluirReparo(manutencao) {
    idManutencaoAtualParaConcluir = manutencao.id_manutencao;
    textareaReparoRealizado.value = '';
    inputMateriaisUtilizados.value = '';
    inputMateriaisUtilizados.disabled = false;
    checkboxNenhumMaterial.checked = false;
    ocultarMensagem(mensagemConcluirReparo);

    nomeEquipamentoModal.textContent = manutencao.nome_equip;
    referenciaEquipamentoModal.textContent = manutencao.referencia_equip;
    ocorrenciaReparoModal.textContent = manutencao.ocorrencia_reparo || 'N/A';

    modalConcluirReparo.classList.add('ativo');
    botaoConfirmarConcluirReparo.classList.remove('oculto');
    alternarSpinner(botaoConfirmarConcluirReparo, spinnerConcluirReparo, false);
}

// Função para fechar o modal de conclusão de reparo
function fecharModalConcluirReparo() {
    modalConcluirReparo.classList.remove('ativo');
    alternarSpinner(botaoConfirmarConcluirReparo, spinnerConcluirReparo, false);
    botaoConfirmarConcluirReparo.classList.remove('oculto');
}

// ... (O restante da função carregarManutencoesTecnico é o mesmo) ...
async function carregarManutencoesTecnico(userId) {
    listaManutencoes.innerHTML = '';
    mensagemCarregamento.classList.remove('oculto');
    ocultarMensagem(mensagemErro);

    console.log(`Carregando manutenções para o técnico ID: ${userId}...`);

    try {
        const response = await fetch(`get_manutencoes_tecnico.php?user_id=${userId}`);
        const data = await response.json();

        console.log('Resposta de get_manutencoes_tecnico.php:', data);

        mensagemCarregamento.classList.add('oculto');

        if (data.success && data.manutencoes.length > 0) {
            data.manutencoes.forEach(manutencao => {
                const itemDiv = document.createElement('div');
                itemDiv.classList.add('item-manutencao');
                itemDiv.dataset.idManutencao = manutencao.id_manutencao;

                let textoDataExecucao = 'Data não definida';
                let diasParaReparo = 0;
                
                if (manutencao.inicio_reparoTec && manutencao.fim_reparoT) {
                    const inicioDate = new Date(manutencao.inicio_reparoTec);
                    const fimDate = new Date(manutencao.fim_reparoT);
                    
                    const inicioFormatado = inicioDate.toLocaleDateString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    
                    const fimFormatado = fimDate.toLocaleDateString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    
                    diasParaReparo = calcularDiasEntreDatas(manutencao.inicio_reparoTec, manutencao.fim_reparoT);
                    textoDataExecucao = `<span class="periodo-data">${inicioFormatado} até ${fimFormatado}</span>`;
                }

                let textoDias = diasParaReparo === 1 ? '1 dia' : `${diasParaReparo} dias`;

                let htmlVeiculos = 'Nenhum veículo atribuído';
                if (manutencao.veiculos_info && manutencao.veiculos_info.trim() !== '') {
                    const veiculos = manutencao.veiculos_info.split(' | ');
                    const spansVeiculos = veiculos.map(veiculo => `<span class="item-veiculo">${veiculo.trim()}</span>`).join(' ');
                    htmlVeiculos = spansVeiculos;
                }

                let conteudo = `
                    <div>
                        <h3 class="titulo-equipamento">
                            ${manutencao.nome_equip} - ${manutencao.referencia_equip}
                        </h3>
                        
                        <p class="linha-data-execucao">
                            ${textoDataExecucao} <span class="total-dias">(${textoDias})</span>
                        </p>
                        
                        <p class="linha-info">
                            <span class="rotulo-info">Cidade:</span> ${manutencao.cidade_nome}
                        </p>
                        
                        <div class="descricao-problema">
                            <span class="rotulo-info">Descrição do problema:</span> ${manutencao.ocorrencia_reparo || 'Não informada'}
                        </div>
                        
                        <p class="linha-info">
                            <span class="rotulo-info">Endereço:</span> ${manutencao.logradouro || 'Não informado'}
                        </p>
                        
                        <div class="lista-veiculos">
                            <span class="rotulo-info">Veículo(s):</span> ${htmlVeiculos}
                        </div>
                    </div>
                `;

                itemDiv.innerHTML = conteudo;

                const divBotoes = document.createElement('div');
                divBotoes.classList.add('botoes-item');

                if (manutencao.latitude && manutencao.longitude) {
                    const botaoLocalizar = document.createElement('button');
                    botaoLocalizar.classList.add('botao-localizar');
                    botaoLocalizar.textContent = 'Localizar no Mapa';
                    botaoLocalizar.addEventListener('click', () => {
                        window.open(`http://maps.google.com/?q=${manutencao.latitude},${manutencao.longitude}`, '_blank');
                    });
                    divBotoes.appendChild(botaoLocalizar);
                }

                const botaoConcluir = document.createElement('button');
                botaoConcluir.classList.add('botao-concluir');
                botaoConcluir.textContent = 'Concluir Reparo';
                botaoConcluir.addEventListener('click', () => {
                    abrirModalConcluirReparo(manutencao);
                });
                divBotoes.appendChild(botaoConcluir);

                const botaoDevolver = document.createElement('button');
                botaoDevolver.classList.add('botao-devolver');
                botaoDevolver.textContent = 'Devolver Reparo';
                botaoDevolver.addEventListener('click', () => {
                    if (confirm('Tem certeza que deseja devolver este reparo? Ele voltará para a fila de atribuição.')) {
                        atualizarStatusManutencao(manutencao.id_manutencao, 'pendente');
                    }
                });
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

// Função para atualizar o status da manutenção
async function atualizarStatusManutencao(idManutencao, novoStatus, descricaoReparo = null, materiaisUtilizados = null) {
    ocultarMensagem(mensagemConcluirReparo);
    alternarSpinner(botaoConfirmarConcluirReparo, spinnerConcluirReparo, true);

    try {
        const response = await fetch('update_manutencao_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_manutencao: idManutencao,
                status_reparo: novoStatus,
                reparo_finalizado: descricaoReparo,
                materiais_utilizados: materiaisUtilizados // Novo campo
            })
        });
        const data = await response.json();

        if (data.success) {
            mostrarMensagem(mensagemConcluirReparo, `Status atualizado com sucesso!`, 'sucesso');
            if (novoStatus === 'concluido') {
                botaoConfirmarConcluirReparo.classList.add('oculto');
            }
            setTimeout(() => {
                fecharModalConcluirReparo();
                carregarManutencoesTecnico(userId);
            }, 1500);
        } else {
            mostrarMensagem(mensagemConcluirReparo, `Erro ao atualizar status: ${data.message}`, 'erro');
        }
    } catch (error) {
        console.error('Erro ao atualizar status da manutenção:', error);
        mostrarMensagem(mensagemConcluirReparo, 'Ocorreu um erro ao tentar atualizar o status da manutenção.', 'erro');
    } finally {
        alternarSpinner(botaoConfirmarConcluirReparo, spinnerConcluirReparo, false);
    }
}

// Event listener para a checkbox
checkboxNenhumMaterial.addEventListener('change', () => {
    if (checkboxNenhumMaterial.checked) {
        inputMateriaisUtilizados.value = 'Nenhum material utilizado';
        inputMateriaisUtilizados.disabled = true;
    } else {
        inputMateriaisUtilizados.value = '';
        inputMateriaisUtilizados.disabled = false;
    }
});
// Event listener para o botão de confirmação do reparo
botaoConfirmarConcluirReparo.addEventListener('click', () => {
    const descricaoReparo = textareaReparoRealizado.value.trim();
    let materiaisUtilizados = inputMateriaisUtilizados.value.trim();

    // Se a checkbox estiver marcada, garante que o valor é "Nenhum material utilizado"
    if (checkboxNenhumMaterial.checked) {
        materiaisUtilizados = 'Nenhum material utilizado';
    } else if (materiaisUtilizados === '') {
        // Se a checkbox não estiver marcada e o campo estiver vazio
        mostrarMensagem(mensagemConcluirReparo, 'Por favor, informe os materiais utilizados ou marque a opção "Nenhum material utilizado".', 'erro');
        return;
    }

    if (descricaoReparo === '') {
        mostrarMensagem(mensagemConcluirReparo, 'Por favor, descreva o reparo realizado.', 'erro');
        return;
    }
    
    if (idManutencaoAtualParaConcluir) {
        atualizarStatusManutencao(idManutencaoAtualParaConcluir, 'concluido', descricaoReparo, materiaisUtilizados);
    } else {
        mostrarMensagem(mensagemConcluirReparo, 'Erro: ID da manutenção não encontrado.', 'erro');
    }
});

window.onclick = function(event) {
    if (event.target == modalConcluirReparo) {
        fecharModalConcluirReparo();
    }
}

// Carrega as manutenções quando a página é carregada
document.addEventListener('DOMContentLoaded', () => {
    if (typeof userId !== 'undefined') {
        carregarManutencoesTecnico(userId);
    } else {
        mensagemErro.textContent = 'Erro: ID do usuário não encontrado.';
        mensagemErro.classList.remove('oculto');
    }
});