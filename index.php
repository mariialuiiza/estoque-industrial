<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

$sqlProdutos = "SELECT COUNT(*) AS total 
                FROM produtos 
                WHERE ativo = 1";

$resultadoProdutos = $conn->query($sqlProdutos);

$totalProdutos = $resultadoProdutos->fetch_assoc()["total"];


$sqlAlertas = "SELECT COUNT(*) AS total
               FROM produtos
               WHERE ativo = 1
               AND estoque_atual <= estoque_minimo";

$resultadoAlertas = $conn->query($sqlAlertas);

$totalAlertas = $resultadoAlertas->fetch_assoc()["total"];


$sqlListaAlertas = "SELECT nome, estoque_atual, estoque_minimo
                    FROM produtos
                    WHERE ativo = 1
                    AND estoque_atual <= estoque_minimo
                    ORDER BY nome ASC";

$listaAlertas = $conn->query($sqlListaAlertas);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Painel de Gestão</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<header class="topo">

    <div>

        <h1>Sistema de Controle de Estoque Industrial</h1>

        <p>Painel de Gestão</p>

    </div>

    <div class="usuario">

        Usuário:
        <strong>
            <?php echo htmlspecialchars($_SESSION["usuario_nome"]); ?>
        </strong>

        <a href="logout.php">Sair</a>

    </div>

</header>


<main class="principal">

    <h2>Painel de Gestão</h2>


    <?php if ($totalAlertas > 0) { ?>

        <div class="alerta">

            <strong>ATENÇÃO!</strong>

            Existem <?php echo $totalAlertas; ?>
            produto(s) com estoque abaixo ou igual ao mínimo.

            <?php while ($alerta = $listaAlertas->fetch_assoc()) { ?>

                <br>

                <?php echo htmlspecialchars($alerta["nome"]); ?>

                -
                Estoque:
                <?php echo $alerta["estoque_atual"]; ?>

                /
                Mínimo:
                <?php echo $alerta["estoque_minimo"]; ?>

            <?php } ?>

        </div>

    <?php } else { ?>

        <div class="sucesso">

            Nenhum produto está abaixo ou igual ao estoque mínimo.

        </div>

    <?php } ?>


    <div class="resumo">

        <div class="caixa">

            <strong>
                <?php echo $totalProdutos; ?>
            </strong>

            <span>Produtos cadastrados</span>

        </div>


        <div class="caixa">

            <strong>
                <?php echo $totalAlertas; ?>
            </strong>

            <span>Alertas de estoque</span>

        </div>

    </div>


    <div class="opcoes">

        <div class="opcao">

            <h3>Cadastro de Produto/Insumo</h3>

            <p>
                Cadastre, pesquise, edite e exclua
                produtos e insumos.
            </p>

            <a href="produtos.php" class="botao">
                Acessar Cadastro
            </a>

        </div>


        <div class="opcao">

            <h3>Gestão de Estoque</h3>

            <p>
                Registre entradas e saídas
                e acompanhe o estoque.
            </p>

            <a href="estoque.php" class="botao">
                Acessar Estoque
            </a>

        </div>

    </div>

</main>

</body>

</html>
</html>