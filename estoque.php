<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

$mensagem = "";
$tipoMensagem = "";


/* REGISTRAR MOVIMENTAÇÃO */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $produto_id = intval($_POST["produto_id"]);
    $tipo = intval($_POST["tipo"]);
    $quantidade = intval($_POST["quantidade"]);
    $data = $_POST["data"];
    $usuario_id = $_SESSION["usuario_id"];


    if ($produto_id <= 0 || $quantidade <= 0 || $data == "") {

        $mensagem = "Preencha todos os campos corretamente.";
        $tipoMensagem = "erro";

    } elseif ($tipo != 1 && $tipo != 2) {

        $mensagem = "Selecione uma movimentação válida.";
        $tipoMensagem = "erro";

    } else {

        $stmt = $conn->prepare(
            "SELECT nome, estoque_atual, estoque_minimo
             FROM produtos
             WHERE id = ? AND ativo = 1"
        );

        $stmt->bind_param("i", $produto_id);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows == 0) {

            $mensagem = "Produto não encontrado.";
            $tipoMensagem = "erro";

        } else {

            $produto = $resultado->fetch_assoc();

            $saldo_anterior = $produto["estoque_atual"];


            if ($tipo == 1) {

                $novo_saldo = $saldo_anterior + $quantidade;

            } else {

                $novo_saldo = $saldo_anterior - $quantidade;

            }


            if ($novo_saldo < 0) {

                $mensagem = "A saída não pode ser maior que o estoque atual.";
                $tipoMensagem = "erro";

            } else {

                $conn->begin_transaction();

                try {

                    $atualizar = $conn->prepare(
                        "UPDATE produtos
                         SET estoque_atual = ?
                         WHERE id = ?"
                    );

                    $atualizar->bind_param(
                        "ii",
                        $novo_saldo,
                        $produto_id
                    );

                    $atualizar->execute();


                    $movimentacao = $conn->prepare(
                        "INSERT INTO movimentacoes
                        (tipo, data, quantidade, saldo_anterior,
                         usuarios_id, produtos_id)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $movimentacao->bind_param(
                        "isiiii",
                        $tipo,
                        $data,
                        $quantidade,
                        $saldo_anterior,
                        $usuario_id,
                        $produto_id
                    );

                    $movimentacao->execute();


                    $conn->commit();


                    if (
                        $tipo == 2 &&
                        $novo_saldo <= $produto["estoque_minimo"]
                    ) {

                        $mensagem =
                            "Movimentação registrada. ATENÇÃO: o produto '" .
                            $produto["nome"] .
                            "' está com estoque abaixo ou igual ao mínimo. " .
                            "Saldo atual: " .
                            $novo_saldo .
                            " | Mínimo: " .
                            $produto["estoque_minimo"];

                        $tipoMensagem = "alerta";

                    } else {

                        $mensagem = "Movimentação registrada com sucesso.";
                        $tipoMensagem = "sucesso";

                    }

                } catch (Exception $e) {

                    $conn->rollback();

                    $mensagem = "Erro ao registrar a movimentação.";
                    $tipoMensagem = "erro";

                }
            }
        }

        $stmt->close();
    }
}


/* LISTA DE PRODUTOS */

$produtos = $conn->query(
    "SELECT *
     FROM produtos
     WHERE ativo = 1
     ORDER BY nome ASC"
);


/* HISTÓRICO */

$historico = $conn->query(
    "SELECT
        m.id,
        m.tipo,
        m.data,
        m.quantidade,
        m.saldo_anterior,
        p.nome AS produto,
        u.nome AS usuario
     FROM movimentacoes m
     INNER JOIN produtos p
        ON p.id = m.produtos_id
     INNER JOIN usuarios u
        ON u.id = m.usuarios_id
     ORDER BY m.data DESC, m.id DESC"
);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Gestão de Estoque</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="container">

    <p>
        <a href="index.php">← Voltar ao Painel</a>
    </p>


    <h1>Gestão de Estoque</h1>


    <?php if ($mensagem != "") { ?>

        <div class="<?php echo $tipoMensagem; ?>">

            <?php echo $mensagem; ?>

        </div>

    <?php } ?>


    <h2>Registrar Movimentação</h2>


    <form method="POST">

        <label>Produto/Insumo:</label>

        <select name="produto_id" required>

            <option value="">
                Selecione um produto
            </option>

            <?php while ($produto = $produtos->fetch_assoc()) { ?>

                <option value="<?php echo $produto["id"]; ?>">

                    <?php echo htmlspecialchars($produto["nome"]); ?>

                    -
                    Estoque:
                    <?php echo $produto["estoque_atual"]; ?>

                    -
                    Mínimo:
                    <?php echo $produto["estoque_minimo"]; ?>

                </option>

            <?php } ?>

        </select>


        <label>Tipo:</label>

        <select name="tipo" required>

            <option value="">
                Selecione
            </option>

            <option value="1">
                Entrada
            </option>

            <option value="2">
                Saída
            </option>

        </select>


        <label>Quantidade:</label>

        <input
            type="number"
            name="quantidade"
            min="1"
            required
        >


        <label>Data:</label>

        <input
            type="date"
            name="data"
            value="<?php echo date("Y-m-d"); ?>"
            required
        >


        <button type="submit">
            Confirmar Movimentação
        </button>

    </form>


    <hr>


    <h2>Produtos em Estoque</h2>


    <table>

        <tr>

            <th>Produto</th>
            <th>Código</th>
            <th>Fabricante</th>
            <th>Estoque Atual</th>
            <th>Estoque Mínimo</th>
            <th>Situação</th>

        </tr>


        <?php

        $produtosLista = $conn->query(
            "SELECT *
             FROM produtos
             WHERE ativo = 1
             ORDER BY nome ASC"
        );

        while ($produto = $produtosLista->fetch_assoc()) {

        ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($produto["nome"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($produto["codigo"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($produto["fabricante"]); ?>
            </td>

            <td>
                <?php echo $produto["estoque_atual"]; ?>
            </td>

            <td>
                <?php echo $produto["estoque_minimo"]; ?>
            </td>

            <td>

                <?php

                if (
                    $produto["estoque_atual"]
                    <=
                    $produto["estoque_minimo"]
                ) {

                    echo "ATENÇÃO - Estoque baixo";

                } else {

                    echo "Normal";

                }

                ?>

            </td>

        </tr>

        <?php } ?>

    </table>


    <hr>


    <h2>Histórico de Movimentações</h2>


    <table>

        <tr>

            <th>Data</th>
            <th>Produto</th>
            <th>Tipo</th>
            <th>Quantidade</th>
            <th>Saldo anterior</th>
            <th>Usuário</th>

        </tr>


        <?php while ($mov = $historico->fetch_assoc()) { ?>

        <tr>

            <td>
                <?php
                echo date(
                    "d/m/Y",
                    strtotime($mov["data"])
                );
                ?>
            </td>

            <td>
                <?php echo htmlspecialchars($mov["produto"]); ?>
            </td>

            <td>

                <?php

                if ($mov["tipo"] == 1) {
                    echo "Entrada";
                } else {
                    echo "Saída";
                }

                ?>

            </td>

            <td>
                <?php echo $mov["quantidade"]; ?>
            </td>

            <td>
                <?php echo $mov["saldo_anterior"]; ?>
            </td>

            <td>
                <?php echo htmlspecialchars($mov["usuario"]); ?>
            </td>

        </tr>

        <?php } ?>

    </table>

</div>

</body>

</html>