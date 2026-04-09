import { loader } from '../index.js';

const language = await loader.language('account/account');

export default class {
    render() {
        return loader.template('account/account', language);
    }
}