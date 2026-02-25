<template>
	<UError
		:error="{
			redirect: '/',
			statusCode: 404,
			statusMessage: 'Page not found',
			message: 'The page you are looking for does not exist.'
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
				@click="$router.back()"
			/>
		</template>
	</UError>
</template>
