<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'tecnico') {
    header('Location: index.html'); // Redireciona se não estiver logado ou não for técnico
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Minhas Manutenções - Técnico</title>
    <style>
        /* Estilos do card e layout geral, consistentes com as outras páginas */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            /* justify-content: center; REMOVIDO: para que o footer vá para o final */
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px; /* Ajustado para melhor visualização das manutenções */
            text-align: center;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-grow: 1; /* Permite que o card cresça para preencher o espaço disponível */
            margin-bottom: 20px; /* Espaço entre o card e o footer */
        }

        .card:before {
            content: none; /* Remove o pseudo-elemento ::before do card */
        }

        .logoMenu {
            width: 150px;
            margin-bottom: 20px;
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        h2 {
            font-size: 2em;
            color: var(--cor-principal);
            margin-bottom: 30px;
            margin-top: 40px;
        }

        .manutencoes-list {
            width: 100%;
            max-height: 60vh; /* Altura máxima para rolagem */
            overflow-y: auto; /* Adiciona rolagem se muitas manutenções */
            padding-right: 10px; /* Espaço para a barra de rolagem */
        }

        .manutencao-item {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .manutencao-item p {
            margin: 0;
            font-size: 1em;
            color: #333;
        }

        .manutencao-item strong {
            color: var(--cor-principal);
        }

        .manutencao-item strong.highlight-label {
            color: var(--cor-terciaria); /* Cor de destaque para labels */
        }

        .item-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap; /* Permite que os botões quebrem linha em telas pequenas */
        }

        .item-buttons button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            flex: 1 1 auto; /* Permite que os botões cresçam e encolham */
            min-width: 120px; /* Largura mínima para botões */
        }

        .concluir-btn {
            background-color: #28a745; /* Verde */
            color: white;
        }

        .concluir-btn:hover {
            background-color: #218838;
        }

        .devolver-btn {
            background-color: #ffc107; /* Amarelo */
            color: #333;
        }

        .devolver-btn:hover {
            background-color: #e0a800;
        }

        .localizar-btn {
            background-color: #007bff; /* Azul */
            color: white;
        }

        .localizar-btn:hover {
            background-color: #0056b3;
        }

        .message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .message.error {
            background-color: #fee2e2;
            color: #ef4444;
            border: 1px solid #fca5a5;
        }

        .message.success {
            background-color: #dcfce7;
            color: #22c55e;
            border: 1px solid #86efac;
        }

        .hidden {
            display: none !important;
        }

        .voltar-btn {
            display: block;
            width: 50%;
            padding: 15px;
            margin-top: 30px;
            text-align: center;
            background-color: var(--botao-voltar);
            color: var(--cor-letra-botaoVoltar);
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }

        .voltar-btn:hover {
            background-color: var(--botao-voltar-hover);
        }

        .footer {
            margin-top: auto; /* Garante que o footer seja empurrado para o final */
            color: #888;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
            padding-top: 20px;
        }

        /* Estilos para os Modais */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow-y: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }
        .modal.is-active {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 500px; /* Ajuste a largura máxima do modal */
            position: relative; /* Essencial para posicionar o botão de fechar */
            text-align: center; /* Alinha o texto dentro do modal ao centro */
        }

        .modal-content h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--cor-principal);
        }

        .modal-content label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
            text-align: left; /* Alinha o label à esquerda */
        }

        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
            min-height: 100px; /* Altura mínima para a caixa de texto */
            resize: vertical; /* Permite redimensionar verticalmente */
        }

        .modal-content button {
            width: 100%;
            padding: 12px;
            background-color: var(--cor-principal);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
            display: flex; /* Para alinhar spinner e texto */
            align-items: center;
            justify-content: center;
        }

        .modal-content button:hover {
            background-color: var(--cor-secundaria);
        }

        .modal-content .close-button {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .modal-content .close-button:hover,
        .modal-content .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Estilos para o spinner dentro do modal */
        .modal-content .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Media Queries para responsividade */
        @media (max-width: 768px) {
            .card {
                padding: 1.5rem;
                width: 95%;
            }
            .logoMenu {
                width: 120px;
                top: -50px;
            }
            h2 {
                font-size: 1.8em;
                margin-top: 30px;
            }
            .manutencao-item {
                padding: 1rem;
            }
            .item-buttons {
                flex-direction: column;
            }
            .item-buttons button {
                width: 100%;
            }
            .voltar-btn {
                width: 70%;
            }
            .modal-content {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .card {
                width: 100%;
                height: auto;
                padding: 10px;
                margin: auto;
            }
            .manutencao-item p {
                font-size: 0.9em;
            }
            .item-buttons button {
                font-size: 0.85em;
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <img class="logoMenu" src="imagens/logo.png" alt="Logo" />
        <h2>Minhas Manutenções</h2>

        <div id="manutencoesList" class="manutencoes-list">
            <p id="loadingMessage">Carregando suas manutenções...</p>
            <p id="errorMessage" class="message error hidden"></p>
        </div>

        <a href="logout.php" class="voltar-btn">Sair</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <!-- Modal para Concluir Reparo -->
    <div id="concluirReparoModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeConcluirReparoModal()">&times;</span>
            <h3>Concluir Reparo</h3>
            <p><strong class="highlight-label">Equipamento:</strong> <span id="modalEquipmentName"></span></p>
            <p><strong class="highlight-label">Ocorrência:</strong> <span id="modalRepairOccurrence"></span></p>
            <label for="reparoRealizadoTextarea">Informe o reparo realizado:</label>
            <textarea id="reparoRealizadoTextarea" rows="5" placeholder="Descreva o trabalho realizado..." required></textarea>
            <button id="confirmConcluirReparoBtn">
                Confirmar Reparo
                <span id="concluirReparoSpinner" class="loading-spinner hidden"></span>
            </button>
            <p id="concluirReparoMessage" class="message hidden"></p>
        </div>
    </div>

    <script>
        const userId = <?php echo json_encode($user_id); ?>;
        const manutencoesList = document.getElementById('manutencoesList');
        const loadingMessage = document.getElementById('loadingMessage');
        const errorMessage = document.getElementById('errorMessage');

        // Referências para o novo modal de conclusão de reparo
        const concluirReparoModal = document.getElementById('concluirReparoModal');
        const modalEquipmentName = document.getElementById('modalEquipmentName'); // Novo
        const modalRepairOccurrence = document.getElementById('modalRepairOccurrence'); // Novo
        const reparoRealizadoTextarea = document.getElementById('reparoRealizadoTextarea');
        const confirmConcluirReparoBtn = document.getElementById('confirmConcluirReparoBtn');
        const concluirReparoSpinner = document.getElementById('concluirReparoSpinner');
        const concluirReparoMessage = document.getElementById('concluirReparoMessage');

        let currentManutencaoIdToConclude = null; // Variável para armazenar o ID da manutenção sendo concluída

        // Funções de utilidade
        function showMessage(element, msg, type) {
            element.textContent = msg;
            element.className = `message ${type}`;
            element.classList.remove('hidden');
        }

        function hideMessage(element) {
            element.classList.add('hidden');
            element.textContent = '';
        }

        // Função para mostrar/esconder spinner e desabilitar/habilitar botão
        function toggleSpinner(button, spinner, show) {
            if (show) {
                spinner.classList.remove('hidden');
                button.disabled = true;
            } else {
                spinner.classList.add('hidden');
                button.disabled = false;
            }
        }

        // Função para calcular o tempo em andamento
        function calculateTimeInProgress(startDateString) {
            if (!startDateString) return 'N/A';

            const startDate = new Date(startDateString);
            const now = new Date();

            const diffMs = now - startDate; // Diferença em milissegundos

            const diffSeconds = Math.floor(diffMs / 1000);
            const diffMinutes = Math.floor(diffSeconds / 60);
            const diffHours = Math.floor(diffMinutes / 60);
            const diffDays = Math.floor(diffHours / 24);

            let result = [];
            if (diffDays > 0) {
                result.push(`${diffDays} dia(s)`);
            }
            const remainingHours = diffHours % 24;
            if (remainingHours > 0) {
                result.push(`${remainingHours} hora(s)`);
            }
            const remainingMinutes = diffMinutes % 60;
            if (remainingMinutes > 0 && diffDays === 0) { // Mostra minutos se for menos de um dia
                result.push(`${remainingMinutes} minuto(s)`);
            } else if (diffMinutes === 0 && diffDays === 0 && remainingHours === 0) {
                result.push('poucos segundos');
            }

            return result.length > 0 ? result.join(', ') : 'N/A';
        }

        // Função para abrir o modal de conclusão de reparo
        function openConcluirReparoModal(manutencao) { // Agora recebe o objeto completo da manutenção
            currentManutencaoIdToConclude = manutencao.id_manutencao;
            reparoRealizadoTextarea.value = ''; // Limpa o campo de texto
            hideMessage(concluirReparoMessage); // Esconde mensagens anteriores

            // Preenche os detalhes do equipamento no modal
            modalEquipmentName.textContent = manutencao.nome_equip;
            modalRepairOccurrence.textContent = manutencao.ocorrencia_reparo || 'N/A';

            concluirReparoModal.classList.add('is-active');
            // Garante que o botão esteja habilitado e o spinner escondido ao abrir o modal
            confirmConcluirReparoBtn.classList.remove('hidden'); // Garante que o botão esteja visível
            toggleSpinner(confirmConcluirReparoBtn, concluirReparoSpinner, false);
        }

        // Função para fechar o modal de conclusão de reparo
        function closeConcluirReparoModal() {
            concluirReparoModal.classList.remove('is-active');
            // Garante que o botão esteja habilitado e o spinner escondido ao fechar o modal
            toggleSpinner(confirmConcluirReparoBtn, concluirReparoSpinner, false);
            confirmConcluirReparoBtn.classList.remove('hidden'); // Garante que o botão esteja visível para a próxima vez
        }

        // Função para carregar as manutenções atribuídas ao técnico
        async function loadManutencoesTecnico() {
            manutencoesList.innerHTML = ''; // Limpa conteúdo anterior
            loadingMessage.classList.remove('hidden');
            hideMessage(errorMessage);

            console.log(`Carregando manutenções para o técnico ID: ${userId}...`);

            try {
                const response = await fetch(`get_manutencoes_tecnico.php?user_id=${userId}`);
                const data = await response.json();

                console.log('Resposta de get_manutencoes_tecnico.php:', data);

                loadingMessage.classList.add('hidden');

                if (data.success && data.manutencoes.length > 0) {
                    data.manutencoes.forEach(manutencao => {
                        const itemDiv = document.createElement('div');
                        itemDiv.classList.add('manutencao-item');
                        itemDiv.dataset.idManutencao = manutencao.id_manutencao;

                        const solicitacaoDate = new Date(manutencao.inicio_reparo);
                        const formattedDate = solicitacaoDate.toLocaleDateString('pt-BR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const tempoEmAndamento = calculateTimeInProgress(manutencao.inicio_reparo);

                        let content = `
                            <p><strong class="highlight-label">Tipo:</strong> ${manutencao.tipo_manutencao.charAt(0).toUpperCase() + manutencao.tipo_manutencao.slice(1)}</p>
                            <p><strong class="highlight-label">Status:</strong> ${manutencao.status_reparo.charAt(0).toUpperCase() + manutencao.status_reparo.slice(1)}</p>
                            <p><strong class="highlight-label">Nome do Equipamento:</strong> ${manutencao.nome_equip}</p>
                            <p><strong class="highlight-label">Referência do Equipamento:</strong> ${manutencao.referencia_equip}</p>
                            <p><strong class="highlight-label">Ocorrência do Reparo:</strong> ${manutencao.ocorrencia_reparo || 'N/A'}</p>
                            <p><strong class="highlight-label">Localização (Cidade):</strong> ${manutencao.cidade_nome}</p>
                            <p><strong>Data da Solicitação:</strong> ${formattedDate}</p>
                            <p><strong>Tempo em Andamento:</strong> ${tempoEmAndamento}</p>
                        `;

                        itemDiv.innerHTML = content;

                        const buttonsDiv = document.createElement('div');
                        buttonsDiv.classList.add('item-buttons');

                        // Botão de Localização (se houver lat/lon)
                        if (manutencao.latitude && manutencao.longitude) {
                            const localizarBtn = document.createElement('button');
                            localizarBtn.classList.add('localizar-btn');
                            localizarBtn.textContent = 'Localizar no Mapa';
                            localizarBtn.addEventListener('click', () => {
                                // Abre o Google Maps com as coordenadas
                                window.open(`https://www.google.com/maps?q=${manutencao.latitude},${manutencao.longitude}`, '_blank');
                            });
                            buttonsDiv.appendChild(localizarBtn);
                        }

                        // Botão Concluir Reparo - AGORA ABRE O MODAL
                        const concluirBtn = document.createElement('button');
                        concluirBtn.classList.add('concluir-btn');
                        concluirBtn.textContent = 'Concluir Reparo';
                        concluirBtn.addEventListener('click', () => {
                            openConcluirReparoModal(manutencao); // Passa o objeto completo da manutenção
                        });
                        buttonsDiv.appendChild(concluirBtn);

                        // Botão Devolver Reparo
                        const devolverBtn = document.createElement('button');
                        devolverBtn.classList.add('devolver-btn');
                        devolverBtn.textContent = 'Devolver Reparo';
                        devolverBtn.addEventListener('click', () => {
                            // Lógica para devolver o reparo (mudar para 'pendente' ou outro status)
                            if (confirm('Tem certeza que deseja devolver este reparo? Ele voltará para a fila de atribuição.')) {
                                updateManutencaoStatus(manutencao.id_manutencao, 'pendente'); // Ou 'cancelado', 'devolvido'
                            }
                        });
                        buttonsDiv.appendChild(devolverBtn);

                        itemDiv.appendChild(buttonsDiv);
                        manutencoesList.appendChild(itemDiv);
                    });
                } else {
                    // MENSAGEM ATUALIZADA AQUI
                    errorMessage.textContent = 'Sem manutenções no momento...';
                    errorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar manutenções do técnico:', error);
                loadingMessage.classList.add('hidden');
                errorMessage.textContent = 'Ocorreu um erro ao carregar suas manutenções. Tente novamente.';
                errorMessage.classList.remove('hidden');
            }
        }

        // Função para atualizar o status da manutenção
        async function updateManutencaoStatus(idManutencao, newStatus, repairDescription = null) {
            hideMessage(concluirReparoMessage); // Esconde mensagens anteriores no modal
            toggleSpinner(confirmConcluirReparoBtn, concluirReparoSpinner, true); // Mostra spinner e desabilita o botão

            try {
                const response = await fetch('update_manutencao_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_manutencao: idManutencao,
                        status_reparo: newStatus,
                        reparo_finalizado: repairDescription // Envia a descrição do reparo
                    })
                });
                const data = await response.json();

                if (data.success) {
                    showMessage(concluirReparoMessage, `Reparo concluído com sucesso!`, 'success');
                    confirmConcluirReparoBtn.classList.add('hidden'); // Oculta o botão Confirmar Reparo
                    setTimeout(() => {
                        closeConcluirReparoModal(); // Fecha o modal
                        loadManutencoesTecnico(); // Recarrega a lista após a atualização
                    }, 1500);
                } else {
                    showMessage(concluirReparoMessage, `Erro ao atualizar status: ${data.message}`, 'error');
                }
            } catch (error) {
                console.error('Erro ao atualizar status da manutenção:', error);
                showMessage(concluirReparoMessage, 'Ocorreu um erro ao tentar atualizar o status da manutenção.', 'error');
            } finally {
                toggleSpinner(confirmConcluirReparoBtn, concluirReparoSpinner, false); // Esconde spinner e reabilita o botão (se não estiver hidden)
            }
        }

        // Event listener para o botão "Confirmar Reparo" dentro do modal
        confirmConcluirReparoBtn.addEventListener('click', () => {
            const repairDescription = reparoRealizadoTextarea.value.trim();
            if (repairDescription === '') {
                showMessage(concluirReparoMessage, 'Por favor, descreva o reparo realizado.', 'error');
                return;
            }
            if (currentManutencaoIdToConclude) {
                updateManutencaoStatus(currentManutencaoIdToConclude, 'concluido', repairDescription);
            } else {
                showMessage(concluirReparoMessage, 'Erro: ID da manutenção não encontrado.', 'error');
            }
        });


        // Carrega as manutenções quando a página é carregada
        document.addEventListener('DOMContentLoaded', loadManutencoesTecnico);

        // Fecha o modal se o usuário clicar fora dele
        window.onclick = function(event) {
            if (event.target == concluirReparoModal) {
                closeConcluirReparoModal();
            }
        }
    </script>
</body>

</html>
