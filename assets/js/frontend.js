/**
 * Controles de acessibilidade do documento convertido:
 * tamanho de texto, alto contraste e leitura em voz alta (Web Speech API).
 *
 * Progressive enhancement: sem JS ou sem suporte do navegador, o conteúdo
 * continua totalmente legível como texto normal — só os controles extras
 * ficam ocultos (atributo `hidden` definido no HTML).
 */
( function () {
	'use strict';

	var i18n = window.cdaFrontendI18n || {};
	var reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var activeStop = null; // função para parar o player atualmente em reprodução (só um por vez).

	function t( key, fallback ) {
		return i18n[ key ] || fallback || key;
	}

	function initToolbar( viewer ) {
		var toolbar = viewer.querySelector( '[data-cda-toolbar]' );
		if ( ! toolbar ) {
			return;
		}
		toolbar.hidden = false;

		var content = viewer.querySelector( '[data-cda-content]' );
		var contrastBtn = viewer.querySelector( '[data-cda-contrast-toggle]' );
		var storageKey = 'cda-prefs-' + viewer.id;
		var scale = 1;

		function loadPrefs() {
			try {
				var saved = JSON.parse( window.localStorage.getItem( storageKey ) || '{}' );
				if ( saved.scale ) {
					scale = saved.scale;
				}
				if ( saved.contrast ) {
					viewer.classList.add( 'cda-high-contrast' );
					if ( contrastBtn ) {
						contrastBtn.setAttribute( 'aria-pressed', 'true' );
					}
				}
			} catch ( e ) {
				// localStorage indisponível: segue com os padrões.
			}
		}

		function savePrefs() {
			try {
				window.localStorage.setItem(
					storageKey,
					JSON.stringify( {
						scale: scale,
						contrast: viewer.classList.contains( 'cda-high-contrast' ),
					} )
				);
			} catch ( e ) {
				// ignora.
			}
		}

		function applyScale() {
			if ( content ) {
				content.style.fontSize = scale + 'em';
			}
		}

		loadPrefs();
		applyScale();

		toolbar.querySelectorAll( '[data-cda-font]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var action = btn.getAttribute( 'data-cda-font' );
				if ( 'increase' === action ) {
					scale = Math.min( 2, Math.round( ( scale + 0.1 ) * 10 ) / 10 );
				} else if ( 'decrease' === action ) {
					scale = Math.max( 0.75, Math.round( ( scale - 0.1 ) * 10 ) / 10 );
				} else {
					scale = 1;
				}
				applyScale();
				savePrefs();
			} );
		} );

		if ( contrastBtn ) {
			contrastBtn.addEventListener( 'click', function () {
				var isOn = viewer.classList.toggle( 'cda-high-contrast' );
				contrastBtn.setAttribute( 'aria-pressed', isOn ? 'true' : 'false' );
				contrastBtn.textContent = isOn ? t( 'contrastOff', 'Desativar alto contraste' ) : t( 'contrastOn', 'Ativar alto contraste' );
				savePrefs();
			} );
		}
	}

	function initAudio( viewer ) {
		var audioControls = viewer.querySelector( '[data-cda-audio]' );
		if ( ! audioControls ) {
			return;
		}

		if ( ! ( 'speechSynthesis' in window ) || typeof window.SpeechSynthesisUtterance === 'undefined' ) {
			return; // Mantém oculto: navegador sem suporte.
		}

		audioControls.hidden = false;

		var synth = window.speechSynthesis;
		var content = viewer.querySelector( '[data-cda-content]' );
		var playBtn = viewer.querySelector( '[data-cda-play]' );
		var playLabel = viewer.querySelector( '[data-cda-play-label]' );
		var stopBtn = viewer.querySelector( '[data-cda-stop]' );
		var voiceSelect = viewer.querySelector( '[data-cda-voice]' );
		var rateInput = viewer.querySelector( '[data-cda-rate]' );
		var statusEl = viewer.querySelector( '[data-cda-status]' );

		var elements = Array.prototype.slice.call(
			content.querySelectorAll( 'h1, h2, h3, h4, h5, h6, p, li, blockquote, figcaption' )
		);
		if ( 0 === elements.length && content ) {
			elements = [ content ];
		}

		var index = 0;
		var state = 'idle'; // idle | playing | paused

		function populateVoices() {
			var voices = synth.getVoices();
			if ( ! voices.length || ! voiceSelect ) {
				return;
			}
			voiceSelect.innerHTML = '';
			voices.forEach( function ( voice, i ) {
				var option = document.createElement( 'option' );
				option.value = i;
				option.textContent = voice.name + ' (' + voice.lang + ')';
				voiceSelect.appendChild( option );
			} );
		}
		populateVoices();
		if ( typeof synth.onvoiceschanged !== 'undefined' ) {
			synth.addEventListener( 'voiceschanged', populateVoices );
		}

		function setStatus( text ) {
			if ( statusEl ) {
				statusEl.textContent = text;
			}
		}

		function clearHighlight() {
			elements.forEach( function ( el ) {
				el.classList.remove( 'cda-reading' );
			} );
		}

		function updateUI() {
			var playing = 'playing' === state;
			var paused = 'paused' === state;

			playBtn.setAttribute( 'aria-pressed', playing ? 'true' : 'false' );
			if ( playLabel ) {
				playLabel.textContent = playing ? t( 'pause', 'Pausar' ) : paused ? t( 'resume', 'Continuar' ) : t( 'play', 'Ouvir' );
			}
			stopBtn.disabled = 'idle' === state;
		}

		function stopUI( statusText ) {
			synth.cancel();
			state = 'idle';
			index = 0;
			clearHighlight();
			updateUI();
			if ( statusText ) {
				setStatus( statusText );
			}
		}

		function speakNext() {
			if ( index >= elements.length ) {
				state = 'idle';
				index = 0;
				clearHighlight();
				updateUI();
				setStatus( t( 'statusDone', 'Leitura concluída.' ) );
				return;
			}

			var el = elements[ index ];
			var text = el.textContent.trim();

			if ( '' === text ) {
				index++;
				speakNext();
				return;
			}

			var utterance = new window.SpeechSynthesisUtterance( text );

			var voices = synth.getVoices();
			if ( voiceSelect && voices[ voiceSelect.value ] ) {
				utterance.voice = voices[ voiceSelect.value ];
			}
			utterance.rate = rateInput ? parseFloat( rateInput.value ) || 1 : 1;

			utterance.onend = function () {
				index++;
				speakNext();
			};
			utterance.onerror = function () {
				index++;
				speakNext();
			};

			clearHighlight();
			el.classList.add( 'cda-reading' );
			el.scrollIntoView( { block: 'center', behavior: reducedMotion ? 'auto' : 'smooth' } );

			synth.speak( utterance );
		}

		playBtn.addEventListener( 'click', function () {
			if ( 'playing' === state ) {
				synth.pause();
				state = 'paused';
				setStatus( t( 'statusPaused', 'Leitura pausada.' ) );
			} else if ( 'paused' === state ) {
				synth.resume();
				state = 'playing';
				setStatus( t( 'statusReading', 'Lendo em voz alta.' ) );
				if ( activeStop && activeStop !== stopUI ) {
					activeStop();
				}
				activeStop = stopUI;
			} else {
				if ( activeStop ) {
					activeStop();
				}
				synth.cancel();
				index = 0;
				state = 'playing';
				setStatus( t( 'statusReading', 'Lendo em voz alta.' ) );
				activeStop = stopUI;
				speakNext();
			}
			updateUI();
		} );

		stopBtn.addEventListener( 'click', function () {
			stopUI( t( 'statusStopped', 'Leitura interrompida.' ) );
		} );

		updateUI();
	}

	function init() {
		document.querySelectorAll( '[data-cda-viewer]' ).forEach( function ( viewer ) {
			initToolbar( viewer );
			initAudio( viewer );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
