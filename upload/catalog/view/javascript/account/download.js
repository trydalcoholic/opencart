import { loader } from '../index.js';

const language = loader.language('account/download');

export default class {
    render() {
        let data = {};

        data.downloads = {};

        return this.load.template('account/download', { ...data, ...language });
    }
}