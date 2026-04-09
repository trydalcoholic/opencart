import { loader } from '../index.js';

// Language
const language = await loader.language('account/edit');

// Library
const session = loader.library('session');

export default class {
    async render() {
        let data = {};

        let customer = session.get('customer');

        data.newsletter = customer.get('newsletter');

        return loader.template('account/newsletter', { ...data, ...language, ...config });
    }

    onSubmit(e) {
        e.preventDefault();

    }
}