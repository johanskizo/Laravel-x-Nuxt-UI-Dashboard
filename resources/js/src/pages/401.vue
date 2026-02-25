<template>
	<UError
		:error="{
			statusCode: 401,
			statusMessage: 'Unauthorized',
			message: 'Unauthenticated.'
		}"
		:clear="false"
	>
		<template #links>
			<UButton
				:label="$t('Login')"
				icon="i-heroicons-arrow-left"
				size="lg"
				color="primary"
				variant="solid"
				@click="$router.push('/')"
			/>
		</template>
	</UError>
</template>
