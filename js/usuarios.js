// Variável global para armazenar a lista de usuários
        let dadosUsuarios = [];

        // Funções de utilidade para mostrar mensagens e spinners
        function exibirMensagem(elemento, mensagem, tipo) {
            elemento.textContent = mensagem;
            elemento.className = `mensagem ${tipo}`;
            elemento.style.display = 'block';
        }

        function alternarCarregamento(botao, spinner, mostrar) {
            if (mostrar) {
                botao.disabled = true;
                spinner.style.display = 'block';
                botao.querySelector('span').style.display = 'none'; // Esconde o texto do botão
            } else {
                botao.disabled = false;
                spinner.style.display = 'none';
                botao.querySelector('span').style.display = 'block'; // Mostra o texto do botão como um elemento de bloco
            }
        }
        
        // Função para fechar o modal de edição
        function fecharModalEdicaoUsuario() {
            document.getElementById('modalEdicaoUsuario').classList.remove('esta-ativo');
            document.getElementById('mensagemEdicaoUsuario').style.display = 'none';
        }

        // Função para fechar o modal de adição
        function fecharModalAdicionarUsuario() {
            document.getElementById('modalAdicionarUsuario').classList.remove('esta-ativo');
            document.getElementById('mensagemAdicionarUsuario').style.display = 'none';
            document.getElementById('formularioAdicionarUsuario').reset();
        }

        /**
         * Abre o modal de adição de usuário.
         * Reseta o estado do formulário e do botão antes de abrir.
         */
        function abrirModalAdicionarUsuario() {
            const formularioAdicionar = document.getElementById('formularioAdicionarUsuario');
            const botaoEnviar = formularioAdicionar.querySelector('.botao-salvar');
            const carregandoAdicionarUsuario = document.getElementById('carregandoAdicionarUsuario');
            const mensagemAdicionarUsuario = document.getElementById('mensagemAdicionarUsuario');
            const campoNomeAdicionar = document.getElementById('nomeAdicionar');
            const campoEmailAdicionar = document.getElementById('emailAdicionar');

            formularioAdicionar.reset();
            alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, false);
            botaoEnviar.style.display = 'flex'; // Usar flex para centralizar o spinner
            mensagemAdicionarUsuario.style.display = 'none';

            // Adiciona um listener para preencher o email com base no nome
            campoNomeAdicionar.addEventListener('input', (evento) => {
                const nomeCompleto = evento.target.value.trim();
                if (nomeCompleto) {
                    // Extrai o primeiro nome, remove espaços e converte para minúsculas
                    const primeiroNome = nomeCompleto.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
                    campoEmailAdicionar.value = `${primeiroNome}@deltaway.com.br`;
                } else {
                    campoEmailAdicionar.value = '';
                }
            });

            document.getElementById('modalAdicionarUsuario').classList.add('esta-ativo');
        }

        /**
         * Abre o modal de edição e preenche com os dados do usuário.
         * A função busca o usuário na lista já carregada na página.
         * @param {number} idUsuario - O ID do usuário a ser editado.
         */
        function abrirModalEdicaoUsuario(idUsuario) {
            // Busca o usuário na lista de dados global
            const usuario = dadosUsuarios.find(u => u.id_usuario == idUsuario);
            
            if (usuario) {
                // Resetar o estado do formulário e do botão antes de preencher
                const formularioEdicao = document.getElementById('formularioEdicaoUsuario');
                const botaoEnviar = formularioEdicao.querySelector('.botao-salvar');
                const carregandoEdicaoUsuario = document.getElementById('carregandoEdicaoUsuario');
                const mensagemEdicaoUsuario = document.getElementById('mensagemEdicaoUsuario');

                alternarCarregamento(botaoEnviar, carregandoEdicaoUsuario, false);
                botaoEnviar.style.display = 'flex'; // Usar flex para centralizar o spinner
                mensagemEdicaoUsuario.style.display = 'none';

                // Preenche os campos do formulário com os dados do usuário encontrado
                document.getElementById('idUsuarioEdicao').value = usuario.id_usuario;
                document.getElementById('nomeEdicao').value = usuario.nome;
                document.getElementById('emailEdicao').value = usuario.email;
                // Use a string vazia se o telefone for nulo
                document.getElementById('telefoneEdicao').value = usuario.telefone || ''; 
                document.getElementById('tipoUsuarioEdicao').value = usuario.tipo_usuario;
                document.getElementById('statusUsuarioEdicao').value = usuario.status_usuario;

                // Mostra o modal
                document.getElementById('modalEdicaoUsuario').classList.add('esta-ativo');
            } else {
                // Caso o usuário não seja encontrado, exibe uma mensagem
                exibirMensagem(document.getElementById('mensagemEdicaoUsuario'), 'Usuário não encontrado. Recarregue a página e tente novamente.', 'erro');
            }
        }

        // Função principal para buscar e renderizar a lista de usuários
        async function buscarEExibirUsuarios(termoPesquisa = '') {
            const containerListaUsuarios = document.getElementById('containerListaUsuarios');
            containerListaUsuarios.innerHTML = 'Carregando usuários...';

            try {
                // Correção: Cria uma URL robusta para evitar erros de análise de caminho
                const url = new URL('API/get_usuario.php', window.location.href);
                url.searchParams.set('search', termoPesquisa);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    // Armazena os dados na variável global
                    dadosUsuarios = data.users;
                    exibirListaUsuarios(dadosUsuarios);
                } else {
                    containerListaUsuarios.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Erro ao buscar usuários:', error);
                containerListaUsuarios.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar os usuários.</div>`;
            }
        }

        // Função para renderizar a lista de usuários na tela
        function exibirListaUsuarios(usuarios) {
            const containerListaUsuarios = document.getElementById('containerListaUsuarios');
            if (usuarios.length === 0) {
                containerListaUsuarios.innerHTML = '<div class="mensagem">Nenhum usuário encontrado.</div>';
                return;
            }

            const htmlListaUsuarios = usuarios.map(usuario => `
                <div class="item-usuario">
                    <h3>${usuario.nome}</h3>
                    <p><strong>E-mail:</strong> ${usuario.email}</p>
                    <p><strong>Telefone:</strong> ${usuario.telefone || 'N/A'}</p>
                    <p><strong>Tipo:</strong> ${usuario.tipo_usuario}</p>
                    <p><strong>Status:</strong> ${usuario.status_usuario}</p>
                    <!-- O onclick agora chama a função abrirModalEdicaoUsuario com o ID do usuário -->
                    <button class="botao-editar" onclick="abrirModalEdicaoUsuario(${usuario.id_usuario})">Editar</button>
                </div>
            `).join('');

            containerListaUsuarios.innerHTML = `<div class="lista-usuarios">${htmlListaUsuarios}</div>`;
        }

        // Event listener para o campo de pesquisa
        document.getElementById('campoPesquisa').addEventListener('input', (evento) => {
            buscarEExibirUsuarios(evento.target.value);
        });

        document.getElementById('formularioEdicaoUsuario').addEventListener('submit', async function(evento) {
    evento.preventDefault();
    const mensagemEdicaoUsuario = document.getElementById('mensagemEdicaoUsuario');
    const botaoEnviar = this.querySelector('.botao-salvar');
    const carregandoEdicaoUsuario = document.getElementById('carregandoEdicaoUsuario');

    const dadosFormulario = new FormData(this);
    const usuario = Object.fromEntries(dadosFormulario.entries());
    usuario.id = document.getElementById('idUsuarioEdicao').value;

    exibirMensagem(mensagemEdicaoUsuario, 'Salvando...', 'sucesso');
    alternarCarregamento(botaoEnviar, carregandoEdicaoUsuario, true);

    try {
        const response = await fetch('API/update_usuario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(usuario)
        });
        const data = await response.json();

        if (data.success) {
            exibirMensagem(mensagemEdicaoUsuario, 'Usuário salvo com sucesso!', 'sucesso');
            botaoEnviar.style.display = 'none';

            setTimeout(() => {
                fecharModalEdicaoUsuario();
                buscarEExibirUsuarios('');
                botaoEnviar.style.display = 'flex';
            }, 1500);
        } else {
            exibirMensagem(mensagemEdicaoUsuario, data.message || 'Erro ao atualizar usuário.', 'erro');
            alternarCarregamento(botaoEnviar, carregandoEdicaoUsuario, false);
        }
    } catch (error) {
        console.error('Erro ao salvar usuário:', error);
        exibirMensagem(mensagemEdicaoUsuario, 'Ocorreu um erro ao salvar o usuário. Tente novamente.', 'erro');
        alternarCarregamento(botaoEnviar, carregandoEdicaoUsuario, false);
    }
});

        // Event listener para o formulário de adição de usuário
        document.getElementById('formularioAdicionarUsuario').addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const mensagemAdicionarUsuario = document.getElementById('mensagemAdicionarUsuario');
            const botaoEnviar = this.querySelector('.botao-salvar');
            const carregandoAdicionarUsuario = document.getElementById('carregandoAdicionarUsuario');

            const dadosFormulario = new FormData(this);
            const usuario = Object.fromEntries(dadosFormulario.entries());

            alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, true);

            try {
                // Faz a chamada real para o novo script PHP
                const response = await fetch('API/add_usuario.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(usuario)
                });
                const data = await response.json();

                if (data.success) {
                    exibirMensagem(mensagemAdicionarUsuario, 'Usuário adicionado com sucesso!', 'sucesso');
                    botaoEnviar.style.display = 'none'; // Esconde o botão após o sucesso

                    setTimeout(() => {
                        fecharModalAdicionarUsuario();
                        buscarEExibirUsuarios(''); // Recarrega a lista
                        botaoEnviar.style.display = 'flex'; // Mostra o botão novamente
                    }, 1500);
                } else {
                    exibirMensagem(mensagemAdicionarUsuario, data.message || 'Erro ao adicionar usuário.', 'erro');
                    alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, false);
                }
            } catch (error) {
                console.error('Erro ao adicionar usuário:', error);
                exibirMensagem(mensagemAdicionarUsuario, 'Ocorreu um erro ao adicionar o usuário. Tente novamente.', 'erro');
                alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, false);
            }
        });

        /**
         * Aplica a máscara de telefone (62) 9 9292-9292 em tempo real.
         * Remove todos os caracteres não numéricos e formata a string.
         * @param {string} valor - O valor do campo de input.
         * @returns {string} - O valor formatado.
         */
        function mascararTelefone(valor) {
            if (!valor) return "";
            valor = valor.replace(/\D/g, ''); // Remove tudo que não é dígito
            
            // (XX) 9 XXXX-XXXX (11 dígitos para celular)
            if (valor.length > 10) {
                valor = valor.replace(/^(\d{2})(\d)(\d{4})(\d{4}).*/, '($1) $2 $3-$4');
            } 
            // (XX) XXXX-XXXX (10 dígitos para telefone fixo)
            else if (valor.length > 6) {
                valor = valor.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
            }
            // (XX) XXXX
            else if (valor.length > 2) {
                valor = valor.replace(/^(\d{2})(\d{4})/, '($1) $2');
            } 
            // (XX)
            else if (valor.length > 0) {
                valor = valor.replace(/^(\d{2})/, '($1)');
            }
            
            return valor;
        }

        // Carrega a lista de usuários quando a página é carregada
        document.addEventListener('DOMContentLoaded', () => {
            buscarEExibirUsuarios();
            
            // Adiciona o event listener para a máscara de telefone
            const campoTelefoneAdicionar = document.getElementById('telefoneAdicionar');
            const campoTelefoneEdicao = document.getElementById('telefoneEdicao');

            campoTelefoneAdicionar.addEventListener('input', (evento) => {
                evento.target.value = mascararTelefone(evento.target.value);
            });

            campoTelefoneEdicao.addEventListener('input', (evento) => {
                evento.target.value = mascararTelefone(evento.target.value);
            });
        });

        // Fecha os modais se o usuário clicar fora
        window.onclick = function(evento) {
            if (evento.target == document.getElementById('modalEdicaoUsuario')) {
                fecharModalEdicaoUsuario();
            }
            if (evento.target == document.getElementById('modalAdicionarUsuario')) {
                fecharModalAdicionarUsuario();
            }
        }