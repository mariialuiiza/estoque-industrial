<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

$mensagem = "";
$tipoMensagem = "";

$editar = false;

$produtoEditar = [
    "id" => "",
    "codigo" => "",
    "nome" => "",
    "fabricante" => "",
    "preco" => "",
    "estoque_atual" => "",
    "estoque_minimo" => "",
    "categoria" => "",
    "material_fabricacao" => ""
];


/* EXCLUIR */

if (isset($_GET["excluir"])) {

    $id = intval($_GET["excluir"]);

    $stmt = $conn->prepare(
        "UPDATE produtos SET ativo = 0 WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $mensagem = "Produto excluído com sucesso.";
        $tipoMensagem = "sucesso";

    } else {

        $mensagem = "Erro ao excluir o produto.";
        $tipoMensagem = "erro";

    }

    $stmt->close();
}


/* EDITAR */

if (isset($_GET["editar"])) {

    $id = intval($_GET["editar"]);

    $stmt = $conn->prepare(
        "SELECT * FROM produtos WHERE id = ? AND ativo = 1"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultadoEditar = $stmt->get_result();

    if ($resultadoEditar->num_rows == 1) {

        $produtoEditar = $resultadoEditar->fetch_assoc();

        $editar = true;

    }

    $stmt->close();
}


/* SALVAR */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = intval($_POST["id"]);

    $codigo = trim($_POST["codigo"]);
    $nome = trim($_POST["nome"]);
    $fabricante = trim($_POST["fabricante"]);
    $preco = $_POST["preco"];
    $estoque_atual = $_POST["estoque_atual"];
    $estoque_minimo = $_POST["estoque_minimo"];
    $categoria = trim($_POST["categoria"]);
    $material = trim($_POST["material_fabricacao"]);


    if (
        $codigo == "" ||
        $nome == "" ||
        $fabricante == "" ||
        $preco == "" ||
        $estoque_atual == "" ||
        $estoque_minimo == ""
    ) {

        $mensagem = "Preencha todos os campos obrigatórios.";
        $tipoMensagem = "erro";

    } elseif (
        !is_numeric($preco) ||
        !is_numeric($estoque_atual) ||
        !is_numeric($estoque_minimo)
    ) {

        $mensagem = "Digite valores válidos para preço e estoque.";
        $tipoMensagem = "erro";

    } elseif (
        $preco < 0 ||
        $estoque_atual < 0 ||
        $estoque_minimo < 0
    ) {

        $mensagem = "Os valores não podem ser negativos.";
        $tipoMensagem = "erro";

    } else {

        if ($id > 0) {

            $stmt = $conn->prepare(
                "UPDATE produtos
                 SET codigo = ?,
                     nome = ?,
                     fabricante = ?,
                     preco = ?,
                     estoque_atual = ?,
                     estoque_minimo = ?,
                     categoria = ?,
                     material_fabricacao = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "sssdiissi",
                $codigo,
                $nome,
                $fabricante,
                $preco,
                $estoque_atual,
                $estoque_minimo,
                $categoria,
                $material,
                $id
            );

            if ($stmt->execute()) {

                $mensagem = "Produto alterado com sucesso.";
                $tipoMensagem = "sucesso";

                $editar = false;

            } else {

                $mensagem = "Erro ao alterar o produto.";
                $tipoMensagem = "erro";

            }

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO produtos
                (codigo, nome, fabricante, preco, estoque_atual,
                 estoque_minimo, ativo, categoria, material_fabricacao)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)"
            );

            $stmt->bind_param(
                "sssdiiss",
                $codigo,
                $nome,
                $fabricante,
                $preco,
                $estoque_atual,
                $estoque_minimo,
                $categoria,
                $material
            );

            if ($stmt->execute()) {

                $mensagem = "Produto cadastrado com sucesso.";
                $tipoMensagem = "sucesso";

            } else {

                $mensagem = "Erro ao cadastrar o produto.";
                $tipoMensagem = "erro";

            }
        }

        $stmt->close();
    }
}


/* BUSCA */

$busca = "";

if (isset($_GET["busca"])) {
    $busca = trim($_GET["busca"]);
}

if ($busca != "") {

    $termo = "%" . $busca . "%";

    $stmt = $conn->prepare(
        "SELECT *
         FROM produtos
         WHERE ativo = 1
         AND (codigo LIKE ? OR nome LIKE ? OR fabricante LIKE ?)
         ORDER BY nome ASC"
    );

    $stmt->bind_param("sss", $termo, $termo, $termo);

    $stmt->execute();

    $produtos = $stmt->get_result();

} else {

    $produtos = $conn->query(
        "SELECT *
         FROM produtos
         WHERE ativo = 1
         ORDER BY nome ASC"
    );
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Cadastro de Produtos</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="container">

    <p>
        <a href="index.php">← Voltar ao Painel</a>
    </p>

    <h1>Cadastro de Produto/Insumo</h1>


    <?php if ($mensagem != "") { ?>

        <div class="<?php echo $tipoMensagem; ?>">

            <?php echo $mensagem; ?>

        </div>

    <?php } ?>


    <h2>
        <?php echo $editar ? "Editar Produto" : "Cadastrar Produto"; ?>
    </h2>


    <form method="POST">

        <input
            type="hidden"
            name="id"
            value="<?php echo $produtoEditar["id"]; ?>"
        >


        <label>Código:</label>

        <input
            type="text"
            name="codigo"
            value="<?php echo htmlspecialchars($produtoEditar["codigo"]); ?>"
            required
        >


        <label>Nome:</label>

        <input
            type="text"
            name="nome"
            value="<?php echo htmlspecialchars($produtoEditar["nome"]); ?>"
            required
        >


        <label>Fabricante:</label>

        <input
            type="text"
            name="fabricante"
            value="<?php echo htmlspecialchars($produtoEditar["fabricante"]); ?>"
            required
        >


        <label>Preço:</label>

        <input
            type="number"
            name="preco"
            step="0.01"
            min="0"
            value="<?php echo $produtoEditar["preco"]; ?>"
            required
        >


        <label>Estoque atual:</label>

        <input
            type="number"
            name="estoque_atual"
            min="0"
            value="<?php echo $produtoEditar["estoque_atual"]; ?>"
            required
        >


        <label>Estoque mínimo:</label>

        <input
            type="number"
            name="estoque_minimo"
            min="0"
            value="<?php echo $produtoEditar["estoque_minimo"]; ?>"
            required
        >


        <label>Categoria:</label>

        <input
            type="text"
            name="categoria"
            value="<?php echo htmlspecialchars($produtoEditar["categoria"]); ?>"
        >


        <label>Material de fabricação:</label>

        <input
            type="text"
            name="material_fabricacao"
            value="<?php echo htmlspecialchars($produtoEditar["material_fabricacao"]); ?>"
        >


        <button type="submit">

            <?php echo $editar ? "Salvar Alteração" : "Cadastrar"; ?>

        </button>


        <?php if ($editar) { ?>

            <a href="produtos.php" class="botao">
                Cancelar
            </a>

        <?php } ?>

    </form>


    <hr>


    <h2>Pesquisar Produtos</h2>

    <form method="GET">

        <input
            type="text"
            name="busca"
            placeholder="Digite nome, código ou fabricante"
            value="<?php echo htmlspecialchars($busca); ?>"
        >

        <button type="submit">
            Pesquisar
        </button>

        <a href="produtos.php" class="botao">
            Limpar
        </a>

    </form>


    <h2>Produtos cadastrados</h2>


    <table>

        <tr>

            <th>Código</th>
            <th>Nome</th>
            <th>Fabricante</th>
            <th>Preço</th>
            <th>Estoque</th>
            <th>Mínimo</th>
            <th>Categoria</th>
            <th>Ações</th>

        </tr>


        <?php while ($produto = $produtos->fetch_assoc()) { ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($produto["codigo"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($produto["nome"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($produto["fabricante"]); ?>
            </td>

            <td>
                R$ <?php echo number_format($produto["preco"], 2, ",", "."); ?>
            </td>

            <td>
                <?php echo $produto["estoque_atual"]; ?>
            </td>

            <td>
                <?php echo $produto["estoque_minimo"]; ?>
            </td>

            <td>
                <?php echo htmlspecialchars($produto["categoria"]); ?>
            </td>

            <td>

                <a href="produtos.php?editar=<?php echo $produto["id"]; ?>">
                    Editar
                </a>

                |

                <a
                    href="produtos.php?excluir=<?php echo $produto["id"]; ?>"
                    onclick="return confirm('Deseja excluir este produto?');"
                >
                    Excluir
                </a>

            </td>

        </tr>

        <?php } ?>

    </table>

</div>

</body>

</html>