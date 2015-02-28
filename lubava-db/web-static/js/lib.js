'use strict';

var Base64;
(function(Base64) {
    Base64.URI_SAFE = 1;

    Base64.encode = function(source, flags) {
        var uri_safe, cod_map, nbytes, i, output, state, output_byte, b, b2;

        if (!(typeof source == 'object' && source && typeof source.length == 'number')) {
            throw Error('Source must be array-like object, was ' + typeof source);
        }
        flags = 0 | flags;

        uri_safe = !!(flags & Base64.URI_SAFE);
        nbytes = source.length;
        output = [];
        i = 0;
        state = 0;
        for (;;) {
            if (i === nbytes) {
                if (state === 0) {
                    break;
                }
                output_byte = b2;
                state = 0;
            } else if (state <= 1) {
                b = source[i];
                i += 1;
                if (state === 0) {
                    output_byte = b >> 2;
                    b2 = (b & 3) << 4;
                    state = 1;
                } else {
                    output_byte = b2 | (b >> 4);
                    b2 = (b & 15) << 2;
                    state = 2;
                }
            } else {
                if (state === 2) {
                    b = source[i];
                    output_byte = b2 | (b >> 6);
                    b2 = b & 63;
                    state = 3;
                } else {
                    output_byte = b2;
                    state = 0;
                    i += 1;
                }
            }
            if (output_byte < 26) {
                output_byte += 65;
            } else if (output_byte < 52) {
                output_byte += 71;
            } else if (output_byte < 62) {
                output_byte -= 4;
            } else if (output_byte === 62) {
                output_byte = uri_safe ? 45 : 43;
            } else {
                output_byte = uri_safe ? 95 : 47;
            }
            output[output.length] = output_byte;
        }

        if (!uri_safe) {
            // Pad with charcodes for '=' until 4 devides the length.
            while (output.length & 3) {
                output[output.length] = 61;
            }
        }

        return String.fromCharCode.apply(null, output);
    }
})(Base64 || (Base64 = {}));

function set_post_key(form) {
    var array, i, random, time_bits, key, elem;
    if (window.crypto && window.crypto.getRandomValues) {
        array = new Uint8Array(8);
        window.crypto.getRandomValues(array);
    } else {
        array = [];
        time_bits = Date.now();
        for (i = 0; i < 8; i += 1) {
            array[array.length] = Math.floor(Math.random() * 256) ^ (time_bits & 255);

            // Use float devision so we can get date bits outside
            // 2**32. Those changes each 50 days.
            time_bits = Math.floor(time_bits / 256);
        }
    }
    key = Base64.encode(array, Base64.URI_SAFE);
    elem = document.createElement('input');
    elem.type = 'hidden';
    elem.name = 'pkey';
    elem.value = key;
    form.appendChild(elem);

    document.cookie = 'pkey=' + key + '; secure; path=/';
    return true;
}
