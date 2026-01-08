/**
 * EXENT Implementation
 * EXtended ENtity Transfer
 *
 * @author Costin Trifan
 * @copyright 2025 Costin Trifan
 * @license MIT
 * @version 1.0.0
 */

let Exent = (function() {
    'use strict';

    class Tokenizer {
        constructor(text) {
            this.text = text;
            this.pos = 0;
            this.length = text.length;
        }

        peek() {
            return this.text[this.pos];
        }

        next() {
            return this.text[this.pos++];
        }

        skipWhitespace() {
            while (this.pos < this.length) {
                const char = this.text[this.pos];
                if (/\s/.test(char)) {
                    this.pos++;
                }
                else if (char === '/' && this.text[this.pos + 1] === '/') {
                    // Single line comment
                    this.pos += 2;
                    while (this.pos < this.length && this.text[this.pos] !== '\n') {
                        this.pos++;
                    }
                }
                else if (char === '/' && this.text[this.pos + 1] === '*') {
                    // Multi line comment
                    this.pos += 2;
                    while (this.pos < this.length && !(this.text[this.pos] === '*' && this.text[this.pos + 1] === '/')) {
                        this.pos++;
                    }
                    this.pos += 2;
                }
                else {
                    break;
                }
            }
        }

        tokenize() {
            const tokens = [];
            while (this.pos < this.length) {
                this.skipWhitespace();
                if (this.pos >= this.length) {
                    break;
                }

                const char = this.peek();

                if (char === '{' || char === '}' || char === '[' || char === ']' || char === ':' || char === ',') {
                    tokens.push({ type: 'punct', value: this.next() });
                }
                else if (char === '"' || char === "'") {
                    tokens.push({ type: 'string', value: this.readQuotedString(this.next()) });
                }
                else if (char === '`') {
                    tokens.push({ type: 'multiline_string', value: this.readMultilineString() });
                }
                else if (char === '@') {
                    tokens.push({ type: 'date', value: this.readDate() });
                }
                else if (char === '&') {
                    this.next();
                    tokens.push({ type: 'anchor', value: this.readIdentifier() });
                }
                else if (char === '*') {
                    this.next();
                    tokens.push({ type: 'ref', value: this.readIdentifier() });
                }
                else {
                    const val = this.readIdentifierOrNumber();
                    if (val === 'true' || val === 'false' || val === 'null') {
                        tokens.push({ type: 'literal', value: val === 'null' ? null : val === 'true' });
                    }
                    else if (val.endsWith('n') && /^\d+n$/.test(val)) {
                        tokens.push({ type: 'bigint', value: BigInt(val.slice(0, -1)) });
                    }
                    else if (val.endsWith('d') && /^\d+(\.\d+)?d$/.test(val)) {
                        tokens.push({ type: 'decimal', value: parseFloat(val.slice(0, -1)) });
                    }
                    else if (/^-?\d+(\.\d+)?([eE][+-]?\d+)?$/.test(val)) {
                        tokens.push({ type: 'number', value: Number(val) });
                    }
                    else {
                        tokens.push({ type: 'identifier', value: val });
                    }
                }
            }
            return tokens;
        }

        readQuotedString(quote) {
            let result = '';
            while (this.pos < this.length) {
                const char = this.next();
                if (char === quote) {
                    break;
                }
                if (char === '\\') {
                    const esc = this.next();
                    switch (esc) {
                        case 'n': {
                            result += '\n';
                            break;
                        }
                        case 'r': {
                            result += '\r';
                            break;
                        }
                        case 't': {
                            result += '\t';
                            break;
                        }
                        case 'b': {
                            result += '\b';
                            break;
                        }
                        case 'f': {
                            result += '\f';
                            break;
                        }
                        case 'u': {
                            result += String.fromCharCode(parseInt(this.text.substr(this.pos, 4), 16));
                            this.pos += 4;
                            break;
                        }
                        default: {
                            result += esc;
                        }
                    }
                }
                else {
                    result += char;
                }
            }
            return result;
        }

        readMultilineString() {
            this.next(); // skip `
            let result = '';
            while (this.pos < this.length) {
                const char = this.next();
                if (char === '`') {
                    break;
                }
                result += char;
            }
            return result;
        }

        readDate() {
            this.next(); // skip @
            let result = '';
            while (this.pos < this.length && /[0-9-T:Z.+-]/.test(this.peek())) {
                result += this.next();
            }
            const date = new Date(result);
            if (isNaN(date.getTime())) {
                throw new Error("Invalid date: " + result);
            }
            return date;
        }

        readIdentifier() {
            let result = '';
            while (this.pos < this.length && /[a-zA-Z0-9_-]/.test(this.peek())) {
                result += this.next();
            }
            return result;
        }

        readIdentifierOrNumber() {
            let result = '';
            // Includes characters for numbers and identifiers
            while (this.pos < this.length && /[a-zA-Z0-9._+-]/.test(this.peek())) {
                result += this.next();
            }
            return result;
        }
    }

    class Parser {
        constructor(tokens, options = {}) {
            this.tokens = tokens;
            this.pos = 0;
            this.anchors = {};
            this.depth = 0;
            this.maxDepth = options.maxDepth || 200;
        }

        peek() {
            return this.tokens[this.pos];
        }
        next() {
            return this.tokens[this.pos++];
        }

        parse() {
            const val = this.parseValue();
            if (this.pos < this.tokens.length) {
                throw new Error("Unexpected token at end of input: " + JSON.stringify(this.peek()));
            }
            return val;
        }

        parseValue() {
            if (this.depth > this.maxDepth) {
                throw new Error("Maximum nesting depth exceeded");
            }
            let anchorName = null;
            if (this.peek() && this.peek().type === 'anchor') {
                anchorName = this.next().value;
            }

            const token = this.peek();
            if (!token) {
                throw new Error("Unexpected end of input");
            }

            let value;
            switch (token.type) {
                case 'punct':
                    if (token.value === '{') {
                        value = {};
                        if (anchorName) {
                            this.anchors[anchorName] = value;
                        }
                        this.depth++;
                        this.parseObject(value);
                        this.depth--;
                    }
                    else if (token.value === '[') {
                        value = [];
                        if (anchorName) {
                            this.anchors[anchorName] = value;
                        }
                        this.depth++;
                        this.parseArray(value);
                        this.depth--;
                    }
                    else {
                        throw new Error("Unexpected punctuation: " + token.value);
                    }
                    break;
                case 'string':
                case 'multiline_string':
                case 'number':
                case 'bigint':
                case 'decimal':
                case 'date':
                case 'literal':
                    value = this.next().value;
                    break;
                case 'identifier':
                    value = this.next().value;
                    break;
                case 'ref':
                    const refName = this.next().value;
                    if (!(refName in this.anchors)) {
                        // Forward references aren't supported in this simple version, 
                        // but we could implement them with a second pass or lazy loading if needed.
                        // For now, assume anchors are defined before use.
                        throw new Error("Undefined reference: " + refName);
                    }
                    value = this.anchors[refName];
                    break;
                default:
                    throw new Error("Unexpected token type: " + token.type);
            }

            if (anchorName) {
                this.anchors[anchorName] = value;
            }
            return value;
        }

        parseObject(obj) {
            this.next(); // {
            while (this.pos < this.tokens.length) {
                this.skipOptionalCommas();
                if (this.peek() && this.peek().type === 'punct' && this.peek().value === '}') {
                    this.next();
                    return obj;
                }

                const keyToken = this.next();
                if (keyToken.type !== 'identifier' && keyToken.type !== 'string' && keyToken.type !== 'literal') {
                    throw new Error("Expected identifier or string as object key, got " + JSON.stringify(keyToken));
                }
                const key = String(keyToken.value);

                const colon = this.next();
                if (!colon || colon.type !== 'punct' || colon.value !== ':') {
                    throw new Error("Expected ':' after key " + key);
                }

                obj[key] = this.parseValue();

                this.skipOptionalCommas();
                if (this.peek() && this.peek().type === 'punct' && this.peek().value === '}') {
                    this.next();
                    return obj;
                }
            }
            throw new Error("Unterminated object");
        }

        parseArray(arr) {
            this.next(); // [
            while (this.pos < this.tokens.length) {
                this.skipOptionalCommas();
                if (this.peek() && this.peek().type === 'punct' && this.peek().value === ']') {
                    this.next();
                    return arr;
                }

                arr.push(this.parseValue());

                this.skipOptionalCommas();
                if (this.peek() && this.peek().type === 'punct' && this.peek().value === ']') {
                    this.next();
                    return arr;
                }
            }
            throw new Error("Unterminated array");
        }

        skipOptionalCommas() {
            while (this.peek() && this.peek().type === 'punct' && this.peek().value === ',') {
                this.next();
            }
        }
    }

    function parse(text, options = {}) {
        const tokenizer = new Tokenizer(text);
        const tokens = tokenizer.tokenize();
        const parser = new Parser(tokens, options);
        return parser.parse();
    }

    function stringify(obj, space = 4) {
        const anchors = new Map();
        const seen = new Map();
        let anchorCount = 0;

        // First pass: detect duplicates for anchors
        function detectAnchors(v) {
            if (v && typeof v === 'object' && !(v instanceof Date)) {
                if (seen.has(v)) {
                    if (!anchors.has(v)) {
                        anchors.set(v, 'a' + (anchorCount++));
                    }
                    return;
                }
                seen.set(v, true);
                if (Array.isArray(v)) {
                    v.forEach(detectAnchors);
                }
                else {
                    for (const k in v) {
                        detectAnchors(v[k]);
                    }
                }
            }
        }
        detectAnchors(obj);

        const writtenAnchors = new Set();

        function serialize(v, indent = '') {
            const nextIndent = indent + (typeof space === 'number' ? ' '.repeat(space) : (space || ''));
            const newline = (typeof space === 'number' || typeof space === 'string') && space !== '' ? '\n' : ' ';
            
            let anchor = '';
            if (v && typeof v === 'object' && !(v instanceof Date)) {
                if (anchors.has(v)) {
                    if (writtenAnchors.has(v)) {
                        return '*' + anchors.get(v);
                    }
                    anchor = '&' + anchors.get(v) + ' ';
                    writtenAnchors.add(v);
                }
            }

            if (v === null) {
                return anchor + 'null';
            }
            if (typeof v === 'boolean') {
                return anchor + v.toString();
            }
            if (typeof v === 'number') {
                return anchor + v.toString();
            }
            if (typeof v === 'bigint') {
                return anchor + v.toString() + 'n';
            }
            if (v instanceof Date) {
                return anchor + '@' + v.toISOString();
            }
            if (typeof v === 'string') {
                if (v.includes('\n')) {
                    return anchor + '`' + v + '`';
                }
                if (/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(v) && !['true', 'false', 'null'].includes(v)) {
                    return anchor + v;
                }
                return anchor + '"' + v.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
            }

            if (Array.isArray(v)) {
                if (v.length === 0) {
                    return anchor + '[]';
                }
                const items = v.map(item => serialize(item, nextIndent));
                return anchor + '[' + newline + items.map(item => nextIndent + item).join(',' + newline) + newline + indent + ']';
            }

            if (typeof v === 'object') {
                const keys = Object.keys(v);
                if (keys.length === 0) {
                    return anchor + '{}';
                }
                const parts = keys.map(k => {
                    const keyStr = /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(k) ? k : '"' + k.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
                    return keyStr + ': ' + serialize(v[k], nextIndent);
                });
                return anchor + '{' + newline + parts.map(p => nextIndent + p).join(',' + newline) + newline + indent + '}';
            }

            return anchor + '"' + String(v).replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"';
        }

        return serialize(obj);
    }

    const TAG_NULL = 0x00;
    const TAG_TRUE = 0x01;
    const TAG_FALSE = 0x02;
    const TAG_INT32 = 0x03;
    const TAG_FLOAT64 = 0x04;
    const TAG_BIGINT64 = 0x05;
    const TAG_STRING = 0x06;
    const TAG_DATE = 0x07;
    const TAG_ARRAY = 0x08;
    const TAG_OBJECT = 0x09;
    const TAG_DECIMAL = 0x0A;
    const TAG_REF = 0x0B;

    function pack(obj) {
        const buffer = [];
        const anchors = new Map();
        let anchorCount = 0;

        function writeByte(b) {
            buffer.push(b & 0xFF);
        }
        function writeUint32(n) {
            writeByte(n >> 24);
            writeByte(n >> 16);
            writeByte(n >> 8);
            writeByte(n);
        }
        function writeFloat64(n) {
            const view = new DataView(new ArrayBuffer(8));
            view.setFloat64(0, n);
            for (let i = 0; i < 8; i++) {
                writeByte(view.getUint8(i));
            }
        }
        function writeBigInt64(n) {
            const view = new DataView(new ArrayBuffer(8));
            view.setBigInt64(0, n);
            for (let i = 0; i < 8; i++) {
                writeByte(view.getUint8(i));
            }
        }
        function writeString(s) {
            const encoder = new TextEncoder();
            const bytes = encoder.encode(s);
            writeUint32(bytes.length);
            for (let b of bytes) {
                writeByte(b);
            }
        }

        function encode(v) {
            if (v === null) {
                writeByte(TAG_NULL);
                return;
            }

            if (typeof v === 'object' && !(v instanceof Date)) {
                if (anchors.has(v)) {
                    writeByte(TAG_REF);
                    writeUint32(anchors.get(v));
                    return;
                }
                anchors.set(v, anchorCount++);
            }

            if (typeof v === 'boolean') {
                writeByte(v ? TAG_TRUE : TAG_FALSE);
            }
            else if (typeof v === 'number') {
                if (Number.isInteger(v) && v >= -2147483648 && v <= 2147483647) {
                    writeByte(TAG_INT32);
                    const view = new DataView(new ArrayBuffer(4));
                    view.setInt32(0, v);
                    for (let i = 0; i < 4; i++) {
                        writeByte(view.getUint8(i));
                    }
                }
                else {
                    writeByte(TAG_FLOAT64);
                    writeFloat64(v);
                }
            }
            else if (typeof v === 'bigint') {
                writeByte(TAG_BIGINT64);
                writeBigInt64(v);
            }
            else if (v instanceof Date) {
                writeByte(TAG_DATE);
                writeFloat64(v.getTime());
            }
            else if (typeof v === 'string') {
                if (v.endsWith('d') && /^\d+(\.\d+)?d$/.test(v)) {
                    writeByte(TAG_DECIMAL);
                    writeFloat64(parseFloat(v.slice(0, -1)));
                }
                else {
                    writeByte(TAG_STRING);
                    writeString(v);
                }
            }
            else if (Array.isArray(v)) {
                writeByte(TAG_ARRAY);
                writeUint32(v.length);
                for (let item of v) {
                    encode(item);
                }
            }
            else if (typeof v === 'object') {
                writeByte(TAG_OBJECT);
                const keys = Object.keys(v);
                writeUint32(keys.length);
                for (let key of keys) {
                    writeString(key);
                    encode(v[key]);
                }
            }
        }

        encode(obj);
        return new Uint8Array(buffer);
    }

    function unpack(uint8Array, options = {}) {
        let pos = 0;
        const anchors = [];
        const view = new DataView(uint8Array.buffer, uint8Array.byteOffset, uint8Array.byteLength);
        let depth = 0;
        const maxDepth = options.maxDepth || 200;

        function readByte() {
            return uint8Array[pos++];
        }
        function readUint32() {
            const val = view.getUint32(pos);
            pos += 4;
            return val;
        }
        function readInt32() {
            const val = view.getInt32(pos);
            pos += 4;
            return val;
        }
        function readFloat64() {
            const val = view.getFloat64(pos);
            pos += 8;
            return val;
        }
        function readBigInt64() {
            const val = view.getBigInt64(pos);
            pos += 8;
            return val;
        }
        function readString() {
            const len = readUint32();
            const bytes = uint8Array.subarray(pos, pos + len);
            pos += len;
            return new TextDecoder().decode(bytes);
        }

        function decode() {
            if (depth > maxDepth) {
                throw new Error("Maximum nesting depth exceeded during unpacking");
            }
            const tag = readByte();
            switch (tag) {
                case TAG_NULL: return null;
                case TAG_TRUE: return true;
                case TAG_FALSE: return false;
                case TAG_INT32: return readInt32();
                case TAG_FLOAT64: return readFloat64();
                case TAG_BIGINT64: return readBigInt64();
                case TAG_STRING: return readString();
                case TAG_DATE: return new Date(readFloat64());
                case TAG_DECIMAL: return readFloat64();
                case TAG_REF:
                    const id = readUint32();
                    return anchors[id];
                case TAG_ARRAY:
                    const arrLen = readUint32();
                    const arr = [];
                    anchors.push(arr);
                    depth++;
                    for (let i = 0; i < arrLen; i++) {
                        arr.push(decode());
                    }
                    depth--;
                    return arr;
                case TAG_OBJECT:
                    const objLen = readUint32();
                    const obj = {};
                    anchors.push(obj);
                    depth++;
                    for (let i = 0; i < objLen; i++) {
                        const key = readString();
                        obj[key] = decode();
                    }
                    depth--;
                    return obj;
                default:
                    throw new Error("Unknown B-EXENT tag: " + tag + " at pos " + (pos - 1));
            }
        }

        return decode();
    }

    return {
        parse,
        stringify,
        pack,
        unpack
    };
})();

// Export if in environment
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Exent;
}
else if (typeof window !== 'undefined') {
    window.Exent = Exent;
}
