<?php


namespace App\Controller\Api;


use App\Business\Usuario;
use App\Controller\Controller;
use App\Model\Entity\Usuarios;
use App\Model\Entity\VwUsuarioimagem;
use App\Model\Render;
use App\Model\SendMail;
use Exception;

class Recovery extends Controller
{

    /**
     * @return VwUsuarioimagem|bool
     * @throws \PHPMailer\PHPMailer\Exception|Exception
     */
    public function recoveryAction()
    {
        $st_codigo = $this->request->getParameter("st_codigo");

        if ($st_codigo) {
            return $this->recoveryConfirmationCodeAction();
        }

        $sendMail = new SendMail();
        $email = $this->request->getParameter("st_email", true, "O e-mail deve ser informado.");


        $usuario = new Usuarios();
        $usuario->setStEmail($email);
        $usuario->mount($usuario->getFirst($usuario->find()));

        if (!$usuario->getIdUsuario()) {
            throw new Exception("O e-mail informado não se encontra na base de dados!");
        }

        $recovery = new \App\Business\Recovery();
        $recoveryEntity = $recovery->salvarSolicitacaoRecovery($usuario->getIdUsuario());

        $parametrosRender = [
            "st_nome" => $usuario->getStNome(),
            "st_codigo" => $recoveryEntity->getStCodigo()
        ];

        $render = new Render();
        $render->setCaminho("Mail/Recovery");
        $stringEmail = $render->renderBeta($parametrosRender);

        return $sendMail->sendEmailSystem($usuario->getStEmail(), "Recuperação de Senha EGPM.", $stringEmail);

    }

    /**
     * @return VwUsuarioimagem
     * @throws Exception
     */
    public function recoveryConfirmationCodeAction()
    {
        $st_codigo = $this->request->getParameter("st_codigo", true, "O código de verificação é obrigatório.");
        $st_email = $this->request->getParameter("st_email", true, "O email deve ser enviado!");

        $recovery = new \App\Business\Recovery();
        $recoveryEntity = $recovery->verificaCodigo($st_codigo);

        if (!$recoveryEntity->getIdUsuario()) {
            throw new Exception("Código informado inválido!");
        }

        $usuario = new Usuario();
        $usuarioEntity = $usuario->getOne($recoveryEntity->getIdUsuario());

        if ($usuarioEntity->getStEmail() === $st_email) {
            return $usuarioEntity;
        }

        throw new Exception("O código informado não foi gerado para o email informado.");

    }

    /**
     * @return VwUsuarioimagem
     * @throws Exception
     */
    public function resetaSenhaAction()
    {
        $usuarioEntity = $this->recoveryConfirmationCodeAction();
        $st_senha = $this->request->getParameter("st_senha", true, "A senha não foi enviada.");
        $usuario = new Usuario();
        $usuario->alterarSenha($usuarioEntity->getIdUsuario(), $st_senha);
        return $usuarioEntity;

    }

}