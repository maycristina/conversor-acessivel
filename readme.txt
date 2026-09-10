=== Conversor de Documentos Acessível ===
Contributors: maycristina
Tags: acessibilidade, pdf, docx, shortcode, text-to-speech
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Converte arquivos PDF, Word (.docx) e TXT em páginas responsivas e acessíveis, com leitura em voz alta, publicáveis via shortcode.

== Description ==

O **Conversor de Documentos Acessível** permite que administradores enviem arquivos PDF, DOCX ou TXT pelo painel do WordPress e transforma cada arquivo em uma página HTML responsiva e acessível (WCAG 2.1 AA), com:

* Controles de tamanho de texto e alto contraste.
* Leitura em voz alta usando a Web Speech API do navegador (sem custo de API externa).
* Contraste, foco visível e navegação por teclado em todos os controles.
* Armazenamento das conversões no banco de dados do WordPress (Custom Post Type), reaproveitáveis via shortcode `[documento_acessivel id="123"]`.
* Shortcode `[cda_instalacoes]` que exibe o número de instalações ativas reportado pela API do WordPress.org (disponível depois que o plugin for publicado no diretório oficial).

= Requisitos =

* PHP 7.4+
* Dependências instaladas via Composer (`composer install` na pasta do plugin) antes da ativação: `smalot/pdfparser` e `phpoffice/phpword`.

= Formatos suportados =

* PDF (texto extraível — PDFs somente-imagem/escaneados não são suportados)
* Word `.docx` (o formato antigo `.doc` do Word 97-2003 não é suportado)
* TXT

== Installation ==

1. Envie a pasta do plugin para `wp-content/plugins/`.
2. Rode `composer install --no-dev` dentro da pasta do plugin.
3. Ative o plugin em **Plugins > Plugins Instalados**.
4. Acesse **Conversor Acessível > Novo Documento** para enviar um arquivo.
5. Copie o shortcode gerado (`[documento_acessivel id="X"]`) e cole em qualquer página ou post.

== Frequently Asked Questions ==

= O contador de instalações funciona antes de eu publicar o plugin no WordPress.org? =

Não. O shortcode `[cda_instalacoes]` consulta a API pública do WordPress.org (`api.wordpress.org/plugins/info`), que só tem dados depois que o plugin é submetido e aprovado no diretório oficial. Até lá, o shortcode fica em branco para visitantes (e mostra um aviso para administradores logados).

= Os arquivos originais enviados ficam guardados? =

Por padrão, não — o plugin extrai o conteúdo e apaga o arquivo original enviado. Isso pode ser alterado em **Conversor Acessível > Configurações**.

= O plugin envia dados para algum servidor externo? =

Só uma chamada, e só se você usar o shortcode `[cda_instalacoes]`: uma consulta à API pública do próprio WordPress.org (`api.wordpress.org/plugins/info`) para buscar o número de instalações ativas deste plugin. Nenhum dado do seu site, dos seus documentos ou dos seus visitantes é enviado a lugar nenhum.

== Screenshots ==

1. Painel administrativo: listagem "Todos os Documentos", com a coluna de shortcode de cada conversão.
2. Documento convertido, com os controles de tamanho de texto, alto contraste e o botão de leitura em voz alta.
3. Leitura em voz alta em andamento, com o parágrafo atual destacado na página.

== Changelog ==

= 1.0.0 =
* Versão inicial: conversão de PDF/DOCX/TXT, shortcode do documento acessível com leitura em voz alta, e shortcode de contagem de instalações via WordPress.org.
