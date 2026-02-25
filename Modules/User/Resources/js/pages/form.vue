<template>
	<DashboardLayout>
		<!-- Toolbar with breadcrumb navigation -->
		<template #toolbar>
			<UDashboardToolbar>
				<template #left>
					<UBreadcrumb :items="breadcrumbItems" />
				</template>
			</UDashboardToolbar>
		</template>

		<!-- Main content: user account and profile form -->
		<template #body>
			<UForm @submit="handleSubmit">
				<div class="grid w-full grid-cols-1 items-start gap-7 lg:grid-cols-2">
					<!-- Left column: account and roles -->
					<div class="space-y-4">
						<UPageCard variant="naked" orientation="horizontal">
							<template #title>{{ $t('User Data') }}</template>
						</UPageCard>

						<UPageCard variant="subtle">
							<!-- Username (readonly) -->
							<UFormField name="name" :label="$t('Username')" required :error="errors.name?.[0]">
								<UInput v-model="form.name" :loading="ui.isLoadingData" readonly class="w-full" />
							</UFormField>

							<USeparator />

							<!-- Email (readonly) -->
							<UFormField name="email" :label="$t('Email')" required :error="errors.email?.[0]">
								<UInput v-model="form.email" :loading="ui.isLoadingData" readonly class="w-full" />
							</UFormField>

							<USeparator />

							<!-- Roles selection -->
							<UFormField name="roles" :label="$t('Role')" :error="errors.roles?.[0]">
								<USelectMenu
									v-model="form.roles"
									v-model:search-term="searchRole"
									:loading="ui.isLoadingData || ui.isLoadingDataRoles"
									class="w-full"
									:search="fetchRoleOptions"
									:items="roleOptions"
									multiple
								/>
							</UFormField>

							<USeparator />

							<!-- Active status -->
							<UFormField name="is_active" :label="$t('Status')" required :error="errors.is_active?.[0]">
								<USelect
									v-model="form.is_active"
									:loading="ui.isLoadingData"
									class="w-full"
									:items="[
										{ label: $t('Active'), value: '1' },
										{ label: $t('Inactive'), value: '0' }
									]"
								/>
							</UFormField>
						</UPageCard>

						<!-- Active login sessions -->
						<UPageCard variant="naked" orientation="horizontal" class="mt-6">
							<template #title>{{ $t('Active Logins') }}</template>
						</UPageCard>

						<div v-if="active_logins.length > 0">
							<UPageCard variant="subtle">
								<ul class="divide-y divide-gray-200 dark:divide-gray-800">
									<li v-for="login in active_logins" class="flex items-center justify-between py-4" :key="login.id">
										<div class="flex items-center gap-3">
											<UIcon name="i-lucide-monitor" class="h-8 w-8 text-gray-400" />
											<div class="flex flex-col">
												<span class="line-clamp-1 text-sm font-medium">{{ login.name }}</span>
												<div class="text-muted-foreground flex flex-col text-xs">
													<span>IP: {{ login.ip_address }}</span>
													<span>{{ $t('Last active') }}: {{ new Date(login.last_used_at).toLocaleString() }}</span>
												</div>
											</div>
										</div>
									</li>
								</ul>
							</UPageCard>
						</div>
						<div v-else>
							<UPageCard variant="subtle" class="flex justify-center">
								<span class="text-center text-sm text-muted italic">
									{{ $t('No active sessions found') }}
								</span>
							</UPageCard>
						</div>
					</div>

					<!-- Right column: profile details and avatar -->
					<div class="space-y-4">
						<UPageCard variant="naked" orientation="horizontal">
							<template #title>{{ $t('Profile') }}</template>
						</UPageCard>

						<UPageCard variant="subtle">
							<!-- Avatar upload with crop modal trigger -->
							<UFormField
								name="photo"
								:label="$t('Photo')"
								description="JPG, GIF or PNG. 2MB Max."
								:error="errors.photo?.[0]"
								class="flex justify-between gap-4 max-sm:flex-col sm:items-center"
							>
								<div class="flex flex-wrap items-center gap-3">
									<UAvatar size="lg" :src="form.photo_url" :alt="form.full_name" />
									<UButton :label="$t('Choose')" color="neutral" @click="onFileClick" />
									<input
										ref="fileRef"
										type="file"
										@change="onFileChange"
										class="hidden"
										accept=".jpg, .jpeg, .png, .gif"
									/>
								</div>
							</UFormField>

							<!-- Identity and personal info -->
							<UFormField
								name="identity_number"
								:label="$t('Identity Number')"
								required
								:error="errors.identity_number?.[0]"
							>
								<UInput v-model="form.identity_number" :loading="ui.isLoadingData" class="w-full" />
							</UFormField>

							<UFormField name="full_name" :label="$t('Full Name')" required :error="errors.full_name?.[0]">
								<UInput v-model="form.full_name" :loading="ui.isLoadingData" class="w-full" />
							</UFormField>

							<!-- Date of birth with calendar popover -->
							<UFormField name="birth_date" :label="$t('Birth Date')" required :error="errors.birth_date?.[0]">
								<UInputDate v-model="form.birth_date" ref="inputDate" :loading="ui.isLoadingData" class="w-full">
									<template #trailing>
										<UPopover :reference="$refs.inputDate?.inputsRef[3]?.$el">
											<UButton icon="i-lucide-calendar" color="neutral" variant="link" size="sm" class="px-0" />
											<template #content>
												<UCalendar v-model="form.birth_date" class="p-2" />
											</template>
										</UPopover>
									</template>
								</UInputDate>
							</UFormField>

							<!-- Gender selection -->
							<UFormField name="gender" :label="$t('Gender')" required :error="errors.gender?.[0]">
								<USelect
									v-model="form.gender"
									:loading="ui.isLoadingData"
									class="w-full"
									:items="[
										{ label: $t('Male'), value: 'male' },
										{ label: $t('Female'), value: 'female' }
									]"
								/>
							</UFormField>

							<!-- Contact & address -->
							<UFormField name="phone_number" :label="$t('Phone Number')" required :error="errors.phone_number?.[0]">
								<UInput v-model="form.phone_number" :loading="ui.isLoadingData" class="w-full" />
							</UFormField>

							<UFormField name="address" :label="$t('Address')" required :error="errors.address?.[0]">
								<UTextarea v-model="form.address" :loading="ui.isLoadingData" class="w-full" />
							</UFormField>
						</UPageCard>
					</div>
				</div>

				<!-- Form actions -->
				<div class="flex items-center justify-end gap-3 py-4">
					<UButton
						:label="$t('Back')"
						icon="i-lucide-arrow-left"
						color="neutral"
						variant="outline"
						@click="$router.back()"
					/>
					<UButton type="submit" :label="$t('Save')" icon="i-lucide-save" :loading="ui.isLoading" color="primary" />
				</div>
			</UForm>
		</template>
	</DashboardLayout>

	<!-- Avatar crop modal -->
	<UModal
		v-model:open="ui.croperModal"
		:title="$t('Crop photo')"
		:description="$t('Adjust the position of your photo')"
		scrollable
	>
		<template #content>
			<cropper
				ref="cropper"
				class="h-128 w-full"
				:src="cropperSrc"
				:stencil-component="$options.components.CircleStencil"
				:stencil-props="{ aspectRatio: 1 }"
				:canvas="{ width: 512, height: 512 }"
			/>
			<UButton
				:label="$t('Apply')"
				icon="i-lucide-check"
				color="primary"
				size="xl"
				@click="handleCroppedImage"
				class="rounded-none"
			/>
		</template>
	</UModal>
</template>

<script>
	/**
	 * ============================================================================
	 * User Detail & Profile Form Page (Options API)
	 * ============================================================================
	 * Edits a user's account status, assigned roles, and extended profile data.
	 * Includes avatar upload with cropping, identity fields, and active login list.
	 * ============================================================================
	 */

	// External libraries & utilities
	import { shallowRef } from 'vue';
	import { CalendarDate } from '@internationalized/date';
	import { CircleStencil, Cropper } from 'vue-advanced-cropper';
	import 'vue-advanced-cropper/dist/style.css';

	// Internal resources (networking & layout)
	import instance from '../../../../../resources/js/src/network/instance';
	import DashboardLayout from '../../../../../resources/js/src/components/DashboardLayout.vue';

	export default {
		// Components used on this page
		components: {
			CircleStencil,
			Cropper,
			DashboardLayout
		},

		// Static (non‑reactive) properties
		debounceTimer: null,

		// Composables and injections
		setup() {
			const toast = useToast();
			return { toast };
		},

		// Local reactive state
		data() {
			return {
				// Route/resource identification
				id: null,
				breadcrumbItems: [
					{ icon: 'i-lucide-box', label: 'Module', to: '/module' },
					{ icon: 'i-lucide-users', label: 'User', to: '/module/user' }
				],

				// UI state flags
				ui: {
					isLoading: false,
					isLoadingData: false,
					isLoadingDataRoles: false,
					croperModal: false
				},

				// Main form payload
				form: {
					name: '',
					email: '',
					is_active: '1',
					photoFile: null,
					photo_url: null,
					photo: null,
					identity_number: '',
					full_name: '',
					birth_date: null,
					gender: null,
					phone_number: '',
					address: '',
					roles: []
				},

				// Reference data & helper state
				searchRole: '',
				roleOptions: [],
				cropperSrc: null,
				active_logins: [],

				// Validation errors from backend
				errors: {}
			};
		},

		// Lifecycle hooks
		mounted() {
			this.fetchRoleOptions();

			if (this.$route.params.id) {
				this.id = this.$route.params.id;
				this.fetchData();
			}
		},

		// Methods grouped by responsibility
		methods: {
			// ====== ROLE OPTIONS & FETCHING ======

			/**
			 * Fetch available roles for the selection menu.
			 * @param {string} query - Search term for filtering roles.
			 */
			async fetchRoleOptions(query = '') {
				this.ui.isLoadingDataRoles = true;
				try {
					const { data: response } = await instance.get('/user/role/options', {
						params: { q: query }
					});
					this.roleOptions = response.data;
				} catch (error) {
					this.handleError(error);
				} finally {
					this.ui.isLoadingDataRoles = false;
				}
			},

			// ====== MAIN DATA FETCHING ======

			/**
			 * Fetch specific user and profile data by ID.
			 */
			async fetchData() {
				this.ui.isLoadingData = true;
				this.errors = {};
				try {
					const { data: response } = await instance.get(`/user/show/${this.id}`);
					const user = response.data.user;

					// Map core account data
					this.form.name = user.name;
					this.form.email = user.email;
					this.form.roles = user.role_names;
					this.form.is_active = String(user.is_active);
					this.active_logins = response.data.active_logins;

					// Map extended profile data if available
					if (user.profile) {
						this.mapProfileToForm(user.profile);
					}

					this.breadcrumbItems.push({
						label: user.name,
						icon: 'i-lucide-pencil'
					});
				} catch (error) {
					this.handleError(error);
				} finally {
					this.ui.isLoadingData = false;
				}
			},

			// ====== FORM SUBMISSION ======

			/**
			 * Submit form data using multipart/form-data payload.
			 */
			async handleSubmit() {
				this.ui.isLoading = true;
				this.errors = {};
				try {
					const formData = this.prepareFormData();
					const { data: response } = await instance.post(`/user/update/${this.id}`, formData, {
						headers: { 'Content-Type': 'multipart/form-data' }
					});

					this.toast.add({
						title: this.$t('Success'),
						description: response.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});

					this.$router.push('/module/user');
				} catch (error) {
					// Validation error handling (Laravel 422 Unprocessable Entity)
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						this.handleError(error);
					}
				} finally {
					this.ui.isLoading = false;
				}
			},

			// ====== MEDIA & FILE HANDLERS ======

			onFileClick() {
				this.$refs.fileRef.click();
			},

			onFileChange(e) {
				const input = e.target;
				if (!input.files?.length) return;
				this.ui.croperModal = true;
				this.cropperSrc = URL.createObjectURL(input.files[0]);
				// Reset to allow re‑selecting the same file
				input.value = null;
			},

			async handleCroppedImage() {
				const { canvas } = this.$refs.cropper.getResult();
				if (canvas) {
					canvas.toBlob((blob) => {
						const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
						this.form.photoFile = file;
						this.form.photo_url = URL.createObjectURL(blob);
						this.ui.croperModal = false;
					}, 'image/jpeg');
				}
			},

			// ====== MAPPING & FORM DATA HELPERS ======

			/**
			 * Maps profile object to form state.
			 */
			mapProfileToForm(profile) {
				this.form.photo_url = profile.photo_url;
				this.form.photo = profile.photo;
				this.form.identity_number = profile.identity_number;
				this.form.full_name = profile.full_name;
				this.form.gender = profile.gender;
				this.form.phone_number = profile.phone_number;
				this.form.address = profile.address;

				if (profile.birth_date) {
					const date = new Date(profile.birth_date);
					this.form.birth_date = shallowRef(new CalendarDate(date.getFullYear(), date.getMonth() + 1, date.getDate()));
				}
			},

			/**
			 * Transforms reactive state into FormData for backend consumption.
			 */
			prepareFormData() {
				const formData = new FormData();
				// Laravel method spoofing for multipart PUT
				formData.append('_method', 'PUT');

				const fields = ['name', 'email', 'is_active', 'identity_number', 'full_name', 'phone_number', 'address'];
				fields.forEach((field) => formData.append(field, this.form[field]));

				formData.append('birth_date', this.form.birth_date || '');
				formData.append('gender', this.form.gender || '');

				if (this.form.roles?.length) {
					this.form.roles.forEach((role) => formData.append('roles[]', role));
				}

				if (this.form.photoFile) {
					formData.append('photo', this.form.photoFile);
				}

				return formData;
			},

			// ====== ERROR HANDLING ======

			/**
			 * Generic error notification helper.
			 */
			handleError(error) {
				this.toast.add({
					title: 'Error',
					description: error.response?.data?.message || this.$t('Something went wrong'),
					icon: 'i-lucide-ban',
					color: 'error'
				});
			}
		},

		// Watchers
		watch: {
			/**
			 * Debounced search for roles to optimize network requests.
			 */
			searchRole(newVal) {
				clearTimeout(this.$options.debounceTimer);
				this.$options.debounceTimer = setTimeout(() => {
					this.fetchRoleOptions(newVal);
				}, 500);
			}
		}
	};
</script>
