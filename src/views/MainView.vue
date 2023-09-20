<template>
	<div class="main-view">
		<LobbyScreen v-if="isInLobby" />
		<template v-else>
			<TopBar :is-in-call="showChatInSidebar" />
			<TransitionWrapper name="fade">
				<ChatView v-if="!showChatInSidebar" />
				<template v-else>
					<CallView v-if="!isBbbEnable" :token="token" />
					<div v-else class="resp-container">
						<iframe class="resp-iframe"
							allow="geolocation; microphone; camera; display-capture;"
							allowfullscreen
							width="100%"
							height="100%"
							frameborder="0"
							:src="src" />
					</div>
				</template>
			</TransitionWrapper>
		</template>
	</div>
</template>

<script>
import CallView from '../components/CallView/CallView.vue'
import ChatView from '../components/ChatView.vue'
import LobbyScreen from '../components/LobbyScreen.vue'
import TopBar from '../components/TopBar/TopBar.vue'
import TransitionWrapper from '../components/TransitionWrapper.vue'

import { useIsInCall } from '../composables/useIsInCall.js'
import isInLobby from '../mixins/isInLobby.js'
import participant from '../mixins/participant.js'

export default {
	name: 'MainView',
	components: {
		ChatView,
		LobbyScreen,
		TopBar,
		CallView,
		TransitionWrapper,
	},

	mixins: [
		isInLobby,
		participant,
	],

	props: {
		token: {
			type: String,
			required: true,
		},
	},

	setup() {
		const isInCall = useIsInCall()
		return { isInCall }
	},

	computed: {
		conversation() {
			return this.$store.getters.conversation(this.token)
		},

		showChatInSidebar() {
			return this.isInCall
		},
		src() {
			return this.$store.getters.getCallUrl()
		},
		isBbbEnable() {
			return this.$store.getters.getBbbStatus
		},
	},

	watch: {
		isInLobby(isInLobby) {
			// User is now blocked by the lobby
			if (isInLobby && this.isInCall) {
				this.$store.dispatch('leaveCall', {
					token: this.token,
					participantIdentifier: this.$store.getters.getParticipantIdentifier(),
				})
			}
		},
	},
	mounted() {
		this.$store.dispatch('getBbbStatus', {
			token: this.token,
		   })
	},

}
</script>

<style lang="scss" scoped>
@import '../assets/variables';

.main-view {
	height: 100%;
	width: 100%;
	display: flex;
	flex-grow: 1;
	flex-direction: column;
	align-content: space-between;
	position: relative;
}
.resp-container {
	position: relative;
	overflow: hidden;
	width: 100%;
	height: 100%;
}
.resp-iframe {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	border: 0;
}
</style>
