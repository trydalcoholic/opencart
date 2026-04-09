import { WebComponent } from '../component.js';
import { loader } from '../index.js';

class XCurrency extends WebComponent {
    static observed = [
        'code',
        'amount',
        'value'
    ];

    get code() {
        return this.getAttribute('code');
    }

    set code(code) {
        this.setAttribute('code', code);
    }

    get amount() {
        return parseFloat(this.getAttribute('amount'));
    }

    set amount(amount) {
        this.setAttribute('amount', amount);
    }

    get value() {
        if (this.hasAttribute('value')) {
            return parseFloat(this.getAttribute('value')).toFixed(this.decimal_place);
        }

        if (this.code in currencies) {
            return currencies[this.code].value;
        } else {
            return 1.00000;
        }
    }

    set value(value) {
        this.setAttribute('value', value);
    }

    async connected() {
        // Library
        this.currency = await loader.library('currency');

        // Storage
        this.currencies = await loader.storage('localisation/currency');
    }

    async render() {
        return this.currency.format(this.value, this.code);
    }
}

customElements.define('x-currency', XCurrency);