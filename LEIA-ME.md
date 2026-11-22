# Sistema WBmusic na Hostinger (PHP + MySQL)

Inclui: cadastro e login por e-mail e senha, recuperação de senha por e-mail, teste de nível, 12 semanas com testes (a próxima só libera após concluir a anterior), painel do tutor, venda do Plano Trimestral (R$ 24,99 por 3 meses) e do E-book (R$ 19,90) com cobrança automática pelo Stripe, e download do e-book em PDF.

Funciona em qualquer hospedagem compartilhada da Hostinger com PHP 8.1 ou superior e MySQL (planos Premium, Business e Cloud). Não precisa de Node nem de terminal.

Perfis:
- **Aluno:** qualquer pessoa que criar acesso.
- **Tutor (administrador):** a conta cujo e-mail for igual a `tutor_email` no config. Crie essa conta normalmente pelo site; ela abre direto no painel do tutor (botão "Testar como aluno" no topo).

## Estrutura

```
public_html/        -> envie TODO o conteúdo desta pasta para o public_html da Hostinger
  index.html
  qr.php            (endereço fixo do QR: /qr)
  logo.png
  .htaccess         (arquivo oculto: confira que subiu)
  api/              (PHP: login, quiz, checkout, webhook, tutor...)
    .htaccess       (bloqueia config.php, lib.php e quiz_data.php)
    config.sample.php
  private/
    ebook.pdf       (só é entregue por /api/download; o acesso direto é bloqueado)
    .htaccess
schema.sql          -> NÃO envie para o site; é só para importar no banco
```

## Passo a passo

1. **PHP:** no hPanel, *Avançado > Configuração do PHP*, escolha PHP 8.1 ou superior. As extensões `pdo_mysql`, `curl` e `mbstring` já vêm ativas.
2. **HTTPS:** ative o SSL do domínio e a opção de forçar HTTPS (o login só funciona em HTTPS).
3. **Banco de dados:** *Bancos de dados > Gerenciamento MySQL*, crie um banco, um usuário e uma senha. Anote os três nomes (a Hostinger coloca um prefixo, como `u123456789_`).
4. **Tabelas:** abra o phpMyAdmin desse banco, aba *Importar*, e envie o arquivo `schema.sql`.
5. **E-mail do site:** em *E-mails*, crie uma conta como `contato@seudominio.com.br`. Ela envia o e-mail de recuperação de senha (SMTP `smtp.hostinger.com`, porta 465).
6. **Enviar os arquivos:** no *Gerenciador de arquivos*, entre em `public_html`, envie o conteúdo da pasta `public_html` deste pacote (pode subir como .zip e extrair) e ative a exibição de arquivos ocultos para conferir os `.htaccess`.
7. **Configurar:** em `public_html/api`, copie `config.sample.php` para `config.php` e preencha banco, `site_url`, `session_secret` (texto aleatório de 40+ caracteres), `tutor_email`, Stripe e SMTP.
8. **Testar a segurança:** abra no navegador `https://seudominio.com.br/api/config.php` e `https://seudominio.com.br/private/ebook.pdf`. Os dois **precisam** dar "403 Forbidden" (acesso negado). Se algum abrir ou baixar, os `.htaccess` não estão ativos: me avise antes de divulgar o site.
9. **Webhook no Stripe** (modo de teste): Developers > Webhooks > Add endpoint.
   - URL: `https://seudominio.com.br/api/webhook`
   - Eventos: `checkout.session.completed` e `checkout.session.async_payment_succeeded`
   - Copie o *Signing secret* (`whsec_...`) para `stripe_webhook_secret` no `config.php`.
10. **Criar a conta do tutor:** abra o site e crie acesso com o e-mail definido em `tutor_email`.
11. **Testar:** crie uma conta de aluno com outro e-mail, escolha um produto e pague com o cartão `4242 4242 4242 4242` (data futura, CVC qualquer). O acesso é liberado em segundos. Teste também "Esqueci minha senha".
12. **Modo real:** crie os dois produtos na conta real do Stripe e troque `stripe_secret`, os dois `price_*` e o `stripe_webhook_secret` (webhook criado no modo real).

## QR Code do flyer (endereço fixo)

O QR gerado pelo site aponta para `https://seudominio.com.br/qr`, que redireciona para o site. Assim o QR impresso nunca perde a validade: se um dia o endereço mudar, basta editar `qr_destino` no `config.php` (ex.: `https://novoendereco.com.br/`) sem reimprimir nada. Deixe `qr_destino` vazio para apontar para o próprio `site_url`.

1. Com o site no ar no domínio definitivo, abra-o, vá em "Escaneie para acessar" e clique em "Baixar QR Code".
2. Escaneie com o celular e confira se abre o site antes de imprimir.
3. Confirme que `https://seudominio.com.br/qr` redireciona (e não dá erro 404).

## Como funciona o acesso

- A Semana 1 e o teste de nível são grátis para quem criar acesso.
- As Semanas 2 a 12 exigem o Plano Trimestral ativo (3 meses a partir do pagamento; um novo pagamento soma mais 3 meses) e ter acertado ao menos 50% do teste da semana anterior. A correção é feita no servidor.
- Cada semana tem o material de estudo (texto, diagramas de teclado e violão, partitura de prática, exercícios com resposta) e, no fim, o botão para o teste. O material segue a mesma liberação do teste: a S1 é grátis; as demais abrem só com o plano ativo e após concluir a semana anterior. O conteúdo fica em `api/material_data.php` (não é acessível direto).
- O Plano e o E-book são independentes: o aluno compra um, os dois ou nenhum e paga o valor de cada um.
- Quem paga pelo link de pagamento direto do Stripe (sem passar pelo site) também é liberado: basta criar o acesso depois com o **mesmo e-mail** da compra.
- O tutor pode liberar ou retirar acesso manualmente (por exemplo, pagamento combinado por fora) e deixar recados para cada aluno.
- Recuperação de senha: o link vale 1 hora e só pode ser usado uma vez. Ao trocar a senha, os acessos abertos em outros aparelhos deixam de valer. Limite de 3 pedidos por hora por e-mail; a resposta é sempre a mesma, exista o cadastro ou não.

## Segurança e manutenção

- Senhas guardadas com hash (`password_hash`); limite de tentativas de login por IP.
- Nunca envie nem compartilhe o `config.php`. Ele guarda a chave do Stripe e a senha do banco.
- A liberação por pagamento só acontece com webhook de assinatura válida e pagamento `paid`, e cada compra é processada uma única vez.
- Para trocar o PDF, substitua `private/ebook.pdf`.
- Faça backup do banco pelo phpMyAdmin (*Exportar*) com regularidade. Ele guarda e-mails e notas dos alunos: trate esses dados conforme a LGPD.
