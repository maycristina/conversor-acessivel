# Conversor de Documentos Acessível

Plugin WordPress que converte arquivos PDF, Word (.docx) e TXT em páginas
responsivas e acessíveis (WCAG 2.1 AA), com leitura em voz alta pelo
navegador. Cada conversão fica salva no banco (Custom Post Type) e pode ser
publicada em qualquer página via shortcode.

## Estrutura do plugin

```
conversor-acessivel.php        Bootstrap: headers do WP, autoload, ativação
includes/
  class-plugin.php             Orquestra o carregamento dos componentes
  class-activator.php          Ativação (opções padrão, flush rewrite)
  class-deactivator.php
  class-post-type.php          CPT "cda_documento" + meta box com o shortcode
  class-converter.php          Dispatcher por extensão de arquivo
  class-converter-interface.php
  class-converter-exception.php
  converters/
    class-pdf-converter.php    smalot/pdfparser
    class-word-converter.php   phpoffice/phpword
    class-txt-converter.php
  class-shortcode.php          [documento_acessivel id="123"]
  class-install-badge.php      [cda_instalacoes] (API do WordPress.org)
admin/
  class-admin.php              Upload + Configurações
templates/
  document-viewer.php          Template acessível (ARIA, controles de áudio)
assets/
  css/frontend.css             Estilos responsivos/acessíveis
  js/frontend.js                Web Speech API + tamanho de texto/contraste
readme.txt                     Formato oficial WordPress.org
uninstall.php                  Limpeza ao desinstalar
composer.json                  Dependências PHP
```

## Como rodar localmente

1. Clone este repositório e instale as dependências PHP:
   ```bash
   git clone https://github.com/maycristina/conversor-acessivel.git
   cd conversor-acessivel
   composer install
   ```
2. Copie (ou faça symlink d)o repositório clonado para
   `wp-content/plugins/conversor-acessivel/` de uma instalação WordPress local
   (ex.: via `wp-env`, Local, ou XAMPP/MAMP).
3. Ative o plugin em **Plugins**.
4. Vá em **Conversor Acessível > Novo Documento**, envie um PDF/DOCX/TXT.
5. Copie o shortcode gerado (mostrado na tela de edição do documento) e cole
   em qualquer página/post: `[documento_acessivel id="X"]`.

## Decisões de arquitetura (e por quê)

- **Armazenamento em Custom Post Type**, não em tabela própria: reaproveita
  `wp_posts`/`wp_postmeta`, revisões, export/backup nativos do WordPress, e a
  tela de listagem do admin sai "de graça".
- **Conversão via bibliotecas PHP (Composer)**, não via API externa: evita
  custo recorrente e dependência de internet no momento da conversão. Como
  contrapartida, PDFs com layout muito complexo ou escaneados (sem texto
  extraível) não são suportados.
- **Leitura em voz alta via Web Speech API do navegador**, não TTS em nuvem:
  zero custo de API, funciona no cliente. A qualidade da voz varia conforme o
  navegador/SO do visitante — é uma limitação aceita nessa versão.
- **Contador de instalações via API oficial do WordPress.org**
  (`[cda_instalacoes]`): não exige infraestrutura própria, mas só retorna
  dados reais depois que o plugin for submetido e aprovado no diretório
  oficial (https://wordpress.org/plugins/developers/). Até lá o shortcode
  fica em branco para visitantes.
- **O texto convertido NÃO passa pelo filtro `the_content`** (apenas por
  `wp_kses_post`): rodar `the_content` executaria `do_shortcode()` sobre texto
  extraído de um arquivo enviado, permitindo que um documento contendo algo
  como `[algum-shortcode]` disparasse shortcodes do site sem intenção.
- **Reconstrução de parágrafos do PDF por heurística**: PDF não tem o conceito
  de parágrafo — só texto posicionado por coordenadas. `class-pdf-converter.php`
  junta linhas quebradas na mesma frase e só fecha um parágrafo quando a linha
  termina em pontuação final (ou há uma linha em branco no PDF). Isso evita o
  "bloco de texto corrido" de uma extração ingênua, mas não reconstrói a
  paragrafação exata do documento original — na ausência de linhas em branco,
  tende a gerar um parágrafo por frase.
- **O "Adicionar Novo" nativo do WordPress para `cda_documento` fica
  desabilitado de propósito** (`capabilities => ['create_posts' => 'do_not_allow']`
  em `class-post-type.php`): documentos só podem ser criados pela tela de
  upload/conversão, nunca em branco pelo editor padrão do WP. A listagem
  "Todos os Documentos" continua normal, com uma coluna extra mostrando o
  shortcode de cada um.

## Hardening (.htaccess / index.php)

- `wp-plugin-conversor-acessivel/.htaccess` (raiz do plugin) nega acesso
  direto via URL a qualquer `.php` do plugin, incluindo os das dependências
  em `vendor/`. Nenhum desses arquivos deveria ser acessado assim — o
  WordPress só os carrega via `include`/`require` no servidor —, e cada
  arquivo já tem um guard `if (!defined('ABSPATH')) exit;` como proteção
  principal (funciona em qualquer servidor). O `.htaccess` é defesa em
  profundidade e só tem efeito em Apache/LiteSpeed com `AllowOverride`
  habilitado; **não se aplica a Nginx**.
- Um `index.php` vazio ("silence is golden") em cada subpasta evita listagem
  de diretório de forma universal (funciona independente do servidor).
- A pasta de upload (`wp-content/uploads/cda-documentos/`) recebe seu próprio
  `.htaccess` + `index.php` na primeira vez que é usada
  (`CDA_Admin::protect_upload_dir()`), negando **execução de scripts**
  (`.php`, `.phtml`, `.cgi`, etc.) — mas não bloqueando acesso geral, já que
  o PDF/DOCX/TXT original precisa continuar servível quando a opção "apagar
  original após converter" está desligada.

## Publicando no WordPress.org (necessário para o badge de instalações)

1. Revise `readme.txt` (headers, tags, `Stable tag`).
2. Rode `composer install --no-dev` e gere o zip do plugin incluindo a pasta
   `vendor/` (o WordPress.org não roda Composer).
3. Siga o processo de submissão em
   https://wordpress.org/plugins/developers/add/.
4. Depois de aprovado, defina o slug correto em
   **Conversor Acessível > Configurações** para o shortcode `[cda_instalacoes]`
   passar a mostrar o número real de instalações ativas.

### Pasta `.wordpress-org/assets/`

Não faz parte do plugin em si (não vai no zip de instalação) — são os
materiais visuais da **página** do plugin no diretório do WordPress.org:

- `icon-128x128.png` / `icon-256x256.png` — já gerados.
- `icon-source.html` — fonte editável do ícone (SVG renderizado via Chromium
  headless); edite e regenere se quiser mudar o design.
- `screenshot-1.png`, `screenshot-2.png`, `screenshot-3.png` — capturas reais
  de uma instalação WordPress rodando o plugin: listagem de documentos no
  admin, documento convertido, e leitura em voz alta em andamento. Legendas
  correspondentes em `== Screenshots ==` no `readme.txt`.

Depois de aprovado no WordPress.org, o conteúdo desta pasta vai para a pasta
`assets/` do repositório SVN (`https://plugins.svn.wordpress.org/conversor-acessivel/assets/`),
que é separada da `trunk/` (código) e do `assets/` interno do plugin
(`assets/css`, `assets/js`).

## Limitações conhecidas

- `.doc` (Word 97-2003) não é suportado — apenas `.docx`.
- PDFs escaneados/somente-imagem não têm texto extraível.
- A leitura em voz alta depende de `speechSynthesis` no navegador do
  visitante; sem suporte, os controles de áudio ficam ocultos automaticamente
  (o texto continua acessível normalmente).

## Licença

GPLv2 ou posterior — veja [LICENSE](LICENSE). Mesma licença do próprio
WordPress, exigida para publicação no diretório oficial de plugins.
