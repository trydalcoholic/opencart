import { loader } from '../index.js';

// Language
const language = loader.language('account/transaction');

export default class {
    render() {
        return loader.template('account/transaction', { ...language });
    }
}