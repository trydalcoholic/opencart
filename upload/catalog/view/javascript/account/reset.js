import { loader } from '../index.js';

const language = await loader.language('account/reset');

export default class {
    render() {


        return loader.template('account/reset', { ...language });
    }
}