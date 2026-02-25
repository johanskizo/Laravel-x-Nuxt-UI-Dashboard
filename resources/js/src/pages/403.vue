<template>
	<UError
		:error="{
			statusCode: 403,
			statusMessage: 'Forbidden',
			message: 'This Action is Unauthorised.'
		}"
		:clear="false"
	>
		<template #links>
			<UButton
				:label="$t('Back')"
				icon="i-heroicons-arrow-left"
				size="lg"
				color="primary"
				variant="solid"
				@click="$router.push('/module')"
			/>
		</template>
	</UError>
</template>
