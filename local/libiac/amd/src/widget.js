/**
 * Floating chat widget for local_libiac: text chat plus push-to-talk voice.
 *
 * Voice: MediaRecorder captures the microphone, the recording is converted in the
 * browser to 16 kHz mono WAV (the backend's speech-to-text does not accept the
 * webm/opus that Chrome records) and posted to /local/libiac/ajax.php, which
 * forwards it to POST /voice/turn. The answer audio (base64) plays automatically.
 *
 * @module     local_libiac/widget
 */
define([], function() {
    var TARGET_RATE = 16000;
    var MAX_RECORD_MS = 60000;
    var REQUEST_TIMEOUT_MS = 90000;
    var AUDIO_MIME = {mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg'};
    var WIDGET_REGION = 'local_libiac-widget';
    var MAX_PAGE_TEXT = 6000;

    /**
     * Encodes mono float samples (-1..1) as a 16-bit PCM WAV.
     *
     * @param {Float32Array} samples
     * @param {Number} rate sample rate in Hz
     * @return {ArrayBuffer}
     */
    var encodeWav = function(samples, rate) {
        var buffer = new ArrayBuffer(44 + samples.length * 2);
        var view = new DataView(buffer);
        var writeText = function(offset, text) {
            for (var i = 0; i < text.length; i++) {
                view.setUint8(offset + i, text.charCodeAt(i));
            }
        };
        writeText(0, 'RIFF');
        view.setUint32(4, 36 + samples.length * 2, true);
        writeText(8, 'WAVE');
        writeText(12, 'fmt ');
        view.setUint32(16, 16, true);
        view.setUint16(20, 1, true); // PCM.
        view.setUint16(22, 1, true); // Mono.
        view.setUint32(24, rate, true);
        view.setUint32(28, rate * 2, true);
        view.setUint16(32, 2, true);
        view.setUint16(34, 16, true);
        writeText(36, 'data');
        view.setUint32(40, samples.length * 2, true);
        for (var i = 0; i < samples.length; i++) {
            var s = Math.max(-1, Math.min(1, samples[i]));
            view.setInt16(44 + i * 2, s < 0 ? s * 0x8000 : s * 0x7fff, true);
        }
        return buffer;
    };

    /**
     * Converts a recorded blob (any format the browser can decode) to a WAV blob.
     *
     * @param {Blob} blob
     * @return {Promise<Blob>}
     */
    var toWavBlob = function(blob) {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        var ctx = new Ctx();
        return blob.arrayBuffer()
            .then(function(data) {
                return ctx.decodeAudioData(data);
            })
            .then(function(decoded) {
                ctx.close();
                var length = Math.max(1, Math.ceil(decoded.duration * TARGET_RATE));
                var offline = new OfflineAudioContext(1, length, TARGET_RATE);
                var source = offline.createBufferSource();
                source.buffer = decoded;
                source.connect(offline.destination);
                source.start();
                return offline.startRendering();
            })
            .then(function(rendered) {
                return new Blob([encodeWav(rendered.getChannelData(0), TARGET_RATE)], {type: 'audio/wav'});
            });
    };

    /**
     * What the user is looking at: page title, path and visible text of the main
     * region (the widget itself lives outside it). Sent with every turn so the tutor
     * can answer about the page; the server limits and treats it as untrusted data.
     *
     * @return {Object}
     */
    var collectPageContext = function() {
        var main = document.querySelector('[role="main"]') || document.querySelector('#region-main');
        var text = main ? (main.innerText || '') : '';
        var params = new URLSearchParams(window.location.search);
        params.delete('sesskey');
        var query = params.toString();
        return {
            page_title: document.title.slice(0, 255),
            page_url: (window.location.pathname + (query ? '?' + query : '')).slice(0, 1000),
            page_text: text.replace(/[ \t]+/g, ' ').replace(/\n\s*\n+/g, '\n').trim().slice(0, MAX_PAGE_TEXT)
        };
    };

    /**
     * @param {Blob} blob
     * @return {Promise<String>} base64 of the blob's bytes
     */
    var blobToBase64 = function(blob) {
        return blob.arrayBuffer().then(function(buffer) {
            var bytes = new Uint8Array(buffer);
            var binary = '';
            for (var i = 0; i < bytes.length; i += 0x8000) {
                binary += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
            }
            return btoa(binary);
        });
    };

    var el = function(tag, className, attributes) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        Object.keys(attributes || {}).forEach(function(name) {
            node.setAttribute(name, attributes[name]);
        });
        return node;
    };

    var init = function(config) {
        if (document.querySelector('[data-region="' + WIDGET_REGION + '"]')) {
            return;
        }
        var strings = config.strings;

        // DOM: floating toggle button + dialog panel, appended to the end of <body>.
        var root = el('div', 'local_libiac-widget', {'data-region': WIDGET_REGION});
        var toggle = el('button', 'btn btn-primary local_libiac-toggle', {
            type: 'button', 'aria-expanded': 'false', 'aria-controls': 'local_libiac-panel',
            'aria-label': strings.open, title: strings.open
        });
        toggle.innerHTML = '&#128172;';
        var panel = el('section', 'local_libiac-panel', {
            id: 'local_libiac-panel', role: 'dialog', 'aria-label': strings.title
        });
        panel.hidden = true;

        var header = el('div', 'local_libiac-header');
        var heading = el('span');
        heading.textContent = strings.title;
        var closeButton = el('button', 'btn btn-sm', {type: 'button', 'aria-label': strings.close, title: strings.close});
        closeButton.innerHTML = '&times;';
        header.appendChild(heading);
        header.appendChild(closeButton);

        var messages = el('div', 'local_libiac-messages', {role: 'log', 'aria-live': 'polite'});
        var status = el('p', 'local_libiac-status text-muted small', {role: 'status'});
        var form = el('form', 'local_libiac-form');
        var input = el('input', 'form-control', {
            type: 'text', maxlength: '4000', autocomplete: 'off',
            'aria-label': strings.messagelabel, placeholder: strings.messageplaceholder
        });
        var sendButton = el('button', 'btn btn-primary', {type: 'submit'});
        sendButton.textContent = strings.send;
        var mic = el('button', 'btn btn-secondary', {
            type: 'button', 'aria-pressed': 'false', 'aria-label': strings.mic_start, title: strings.mic_start
        });
        mic.innerHTML = '&#127908;';
        form.appendChild(input);
        form.appendChild(sendButton);
        form.appendChild(mic);

        panel.appendChild(header);
        panel.appendChild(messages);
        panel.appendChild(status);
        panel.appendChild(form);
        root.appendChild(panel);
        root.appendChild(toggle);
        document.body.appendChild(root);

        var busy = false;
        var recorder = null;
        var stream = null;
        var chunks = [];
        var stopTimer = null;

        var setStatus = function(text) {
            status.textContent = text || '';
        };

        var setBusy = function(value) {
            busy = value;
            input.disabled = value;
            sendButton.disabled = value;
            mic.disabled = value && !recorder;
            setStatus(value ? strings.processing : '');
        };

        // Message text is always inserted with textContent, never as HTML.
        var addMessage = function(kind, who, text) {
            var item = el('div', 'local_libiac-msg local_libiac-msg-' + kind);
            if (who) {
                var label = el('span', 'local_libiac-msg-who');
                label.textContent = who;
                item.appendChild(label);
            }
            var body = el('span');
            body.textContent = text;
            item.appendChild(body);
            messages.appendChild(item);
            messages.scrollTop = messages.scrollHeight;
            return item;
        };

        var showError = function(key) {
            addMessage('error', '', strings['error_' + key] || strings.error_unavailable);
        };

        var playAnswer = function(item, base64, format) {
            var audio = el('audio', '', {controls: 'controls'});
            audio.src = 'data:' + (AUDIO_MIME[format] || 'audio/mpeg') + ';base64,' + base64;
            item.appendChild(audio);
            // Autoplay can be refused by the browser; the controls stay visible as fallback.
            var started = audio.play();
            if (started && started.catch) {
                started.catch(function() {
                    return null;
                });
            }
        };

        var post = function(action, body, contentType) {
            var url = config.ajaxurl + '?action=' + action + '&courseid=' + config.courseid +
                '&sesskey=' + encodeURIComponent(config.sesskey);
            var controller = new AbortController();
            var timer = setTimeout(function() {
                controller.abort();
            }, REQUEST_TIMEOUT_MS);
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': contentType},
                body: body,
                signal: controller.signal
            }).then(function(response) {
                return response.json().catch(function() {
                    return {error: 'unavailable'};
                }).then(function(data) {
                    if (!response.ok || data.error) {
                        throw {key: data.error || 'unavailable'};
                    }
                    return data;
                });
            }).then(function(data) {
                clearTimeout(timer);
                return data;
            }, function(error) {
                clearTimeout(timer);
                throw error.key ? error : {key: 'unavailable'};
            });
        };

        var sendText = function(text) {
            addMessage('user', strings.you, text);
            setBusy(true);
            post('chat', JSON.stringify({message: text, page_context: collectPageContext()}), 'application/json').then(function(data) {
                addMessage('assistant', strings.assistant, data.message);
            }).catch(function(error) {
                showError(error.key);
            }).then(function() {
                setBusy(false);
                input.focus();
            });
        };

        var sendAudio = function(wav) {
            setBusy(true);
            blobToBase64(wav).then(function(audio) {
                return post(
                    'voice',
                    JSON.stringify({audio_base64: audio, page_context: collectPageContext()}),
                    'application/json'
                );
            }).then(function(data) {
                addMessage('user', strings.you, data.transcript);
                var item = addMessage('assistant', strings.assistant, data.response_text);
                if (data.audio_base64) {
                    playAnswer(item, data.audio_base64, data.audio_format);
                }
            }).catch(function(error) {
                showError(error.key);
            }).then(function() {
                setBusy(false);
            });
        };

        var setMicState = function(recording) {
            mic.setAttribute('aria-pressed', recording ? 'true' : 'false');
            var label = recording ? strings.mic_stop : strings.mic_start;
            mic.setAttribute('aria-label', label);
            mic.title = label;
            setStatus(recording ? strings.recording : '');
        };

        var releaseStream = function() {
            if (stream) {
                stream.getTracks().forEach(function(track) {
                    track.stop();
                });
            }
            stream = null;
        };

        var stopRecording = function() {
            clearTimeout(stopTimer);
            if (recorder && recorder.state !== 'inactive') {
                recorder.stop();
            }
        };

        var startRecording = function() {
            navigator.mediaDevices.getUserMedia({audio: true}).then(function(media) {
                stream = media;
                chunks = [];
                recorder = new MediaRecorder(media);
                recorder.ondataavailable = function(event) {
                    if (event.data && event.data.size > 0) {
                        chunks.push(event.data);
                    }
                };
                recorder.onstop = function() {
                    var recorded = new Blob(chunks, {type: recorder.mimeType});
                    recorder = null;
                    releaseStream();
                    setMicState(false);
                    setBusy(true);
                    toWavBlob(recorded).then(function(wav) {
                        setBusy(false);
                        sendAudio(wav);
                    }).catch(function() {
                        setBusy(false);
                        showError('unsupported');
                    });
                };
                recorder.start();
                setMicState(true);
                stopTimer = setTimeout(stopRecording, MAX_RECORD_MS);
            }).catch(function() {
                releaseStream();
                recorder = null;
                setStatus(strings.micdenied);
            });
        };

        var setOpen = function(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                input.focus();
            } else {
                stopRecording();
                toggle.focus();
            }
        };

        toggle.addEventListener('click', function() {
            setOpen(panel.hidden);
        });
        closeButton.addEventListener('click', function() {
            setOpen(false);
        });
        panel.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            var text = input.value.trim();
            if (busy || !text) {
                return;
            }
            input.value = '';
            sendText(text);
        });

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
            mic.hidden = true;
            setStatus(strings.micunsupported);
            return;
        }
        mic.addEventListener('click', function() {
            if (recorder) {
                stopRecording();
            } else if (!busy) {
                startRecording();
            }
        });
    };

    return {init: init, encodeWav: encodeWav};
});
