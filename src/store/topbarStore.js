const state = {
	hide: false,
}

const getters = {
	getTopbarStatus: (state) => {
		return state.hide
	},
}

const mutations = {
	/**
	 * Hides the topbar
	 *
	 * @param {object} state current store state;
	 */
	hideTopbar(state) {
		state.hide = true
	},
	/**
	 * Show the topbar
	 *
	 * @param {object} state current store state;
	 */
	showTopbar(state) {
		state.hide = false
	},
}

const actions = {

	/**
	 * Hides the topbar
	 *
	 * @param {object} context default store context;
	 */
	hideTopbar(context) {
		context.commit('hideTopbar')
	},
	/**
	 * Hides the topbar
	 *
	 * @param {object} context default store context;
	 */
	showTopbar(context) {
		context.commit('showTopbar')
	},
}

export default { state, mutations, getters, actions }
