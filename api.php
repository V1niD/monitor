<?php
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $servidor = $_POST['servidor'] ?? 'Desconhecido';
    $cpu = (float) ($_POST['cpu'] ?? 0);
    $ram = (float) ($_POST['ram'] ?? 0);
    $disco = (float) ($_POST['disco'] ?? 0);
    $armazenamento = (float) ($_POST['armazenamento'] ?? 0);
    $timestamp = date("Y-m-d H:i:s");

    $uri = "mongodb+srv://Monitoramento:123@cluster0.mg3nr90.mongodb.net/?appName=Cluster0";

    try {
        $manager = new MongoDB\Driver\Manager($uri);
        
        $bulk = new MongoDB\Driver\BulkWrite;
        $documento = [
            'servidor' => $servidor,
            'cpu' => $cpu,
            'ram' => $ram,
            'disco' => $disco,
            'armazenamento' => $armazenamento,
            'data_hora' => $timestamp
        ];
        
        $bulk->insert($documento);

        $manager->executeBulkWrite('db_infra.historico_hw', $bulk);

        echo json_encode(["status" => "sucesso", "mensagem" => "Dados salvos no Atlas com sucesso!"]);

    } catch (MongoDB\Driver\Exception\Exception $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro no MongoDB: " . $e->getMessage()]);
    }

} else {
    echo "<h1>API de Monitoramento Ativa</h1>";
    echo "<p>O PHP está pronto para receber os dados e enviar para o MongoDB Atlas.</p>";
}
?>
