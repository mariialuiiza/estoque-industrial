<?php

session_start();

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once "config.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $senha = trim($_POST["senha"]);

    if ($email == "" || $senha == "") {

        $erro = "Preencha o e-mail e a senha.";

    } else {

        $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ? AND ativo = 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows == 1) {

            $usuario = $resultado->fetch_assoc();

            if ($senha == $usuario["senha"]) {

                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nome"] = $usuario["nome"];

                header("Location: index.php");
                exit;

            } else {

                $erro = "Senha incorreta.";

            }

        } else {

            $erro = "Usuário não encontrado ou inativo.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Login - Controle de Estoque</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="login-container">

    <h1>Controle de Estoque</h1>

    <h2>Login</h2>

    <?php if ($erro != "") { ?>

        <div class="alerta">
            <?php echo $erro; ?>
        </div>

    <?php } ?>

    <form method="POST">

        <label>E-mail:</label>

        <input type="email" name="email" required>

        <label>Senha:</label>

        <input type="password" name="senha" required>

        <button type="submit">Entrar</button>

    </form>

</div>

</body>

</html>