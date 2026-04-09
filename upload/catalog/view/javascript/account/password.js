import { loader } from '../index.js';

// Library
const session = loader.library('session');

// Language
const language = loader.language('account/password');

export default class {
    render() {
        let data = {};

        return loader.template('account/password', { ...data, ...language });
    }

    onSubmit(e) {
        e.preventDefault();




    }
}