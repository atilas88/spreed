import { getBbbStatus } from '../services/callsService.js'

const state = {
	enable: false,
}

const getters = {
	getBbbStatus: (state) => {
		return state.enable
	},
}

const mutations = {
	/**
	 * Set the status of the Bbb
	 *
	 * @param {object} state current store state;
	 * @param {data} data data to be set;
	 */
	setBbbStatus(state, data) {
		state.enable = data
	},

}

const actions = {

	/**
	 * Set the status of the Bbb
	 *
	 * @param {object} context default store context;
	 * @param {string} token secret token;
	 */
	async getBbbStatus(context, { token }) {
		const statusBbb = await getBbbStatus(token)
		context.commit('setBbbStatus', statusBbb)
	},
}

export default { state, mutations, getters, actions }
