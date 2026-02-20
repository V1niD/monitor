<?php
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Recebe os dados do Python (convertendo para números)
    $servidor = $_POST['servidor'] ?? 'Desconhecido';
    $cpu = (float) ($_POST['cpu'] ?? 0);
    $ram = (float) ($_POST['ram'] ?? 0);
    $disco = (float) ($_POST['disco'] ?? 0);
    $armazenamento = (float) ($_POST['armazenamento'] ?? 0);
    $timestamp = date("Y-m-d H:i:s");

    // =========================================================================
    // 2. COLE SUA STRING DE CONEXÃO DO MONGODB ATLAS AQUI
    // Exemplo: "mongodb+srv://admin_monitoramento:suasenha123@cluster0.abcde.mongodb.net/..."
    // =========================================================================
    $uri = "mongodb+srv://Monitoramento:123@cluster0.mg3nr90.mongodb.net/?appName=Cluster0";

    try {
        // 3. Inicia a conexão com a nuvem
        $manager = new MongoDB\Driver\Manager($uri);
        
        // 4. Prepara a "caixa" (pacote de dados) para envio
        $bulk = new MongoDB\Driver\BulkWrite;
        $documento = [
            'servidor' => $servidor,
            'cpu' => $cpu,
            'ram' => $ram,
            'disco' => $disco,
            'armazenamento' => $armazenamento,
            'data_hora' => $timestamp
        ];
        
        // Adiciona o documento ao pacote
        $bulk->insert($documento);

        // 5. Executa a gravação. 
        // 'db_infra' é o nome do banco. 'historico_hw' é a coleção (tabela).
        $manager->executeBulkWrite('db_infra.historico_hw', $bulk);

        // Responde ao Python que deu tudo certo
        echo json_encode(["status" => "sucesso", "mensagem" => "Dados salvos no Atlas com sucesso!"]);

    } catch (MongoDB\Driver\Exception\Exception $e) {
        // Se der erro de senha ou internet, o PHP avisa
        echo json_encode(["status" => "erro", "mensagem" => "Erro no MongoDB: " . $e->getMessage()]);
    }

} else {
    echo "<h1>API de Monitoramento Ativa</h1>";
    echo "<p>O PHP está pronto para receber os dados e enviar para o MongoDB Atlas.</p>";
}
?>