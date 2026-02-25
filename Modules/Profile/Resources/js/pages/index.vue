<template>
	<DashboardLayout>
		<template #toolbar>
			<UDashboardToolbar>
				<NavigationMenu />
			</UDashboardToolbar>
		</template>

		<template #body>
			<div class="mx-auto flex w-full flex-col">
				<UForm @submit="handleSubmit">
					<UPageCard variant="subtle">
						<UFormField
							name="photo"
							:label="$t('Photo')"
							:error="errors.photo?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex flex-col items-start gap-4 sm:flex-row sm:items-center"
						>
							<div class="flex flex-wrap items-start gap-3">
								<UAvatar :src="form.photo_url" :alt="form.full_name" size="lg" />
								<UButton :label="$t('Choose')" color="neutral" @click="onFileClick" />
								<input
									type="file"
									class="hidden"
									ref="fileRef"
									accept=".jpg, .jpeg, .png, .gif"
									@change="onFileChange"
								/>
							</div>
						</UFormField>

						<UFormField
							name="identity_number"
							:label="$t('Identity Number')"
							required
							:error="errors.identity_number?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.identity_number" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="full_name"
							:label="$t('Full Name')"
							required
							:error="errors.full_name?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.full_name" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="birth_date"
							:label="$t('Birth Date')"
							required
							:error="errors.birth_date?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInputDate
								v-model="form.birth_date"
								:loading="ui.isLoadingData"
								class="w-100"
								:locale="locale"
								ref="inputDate"
							>
								<template #trailing>
									<UPopover :reference="$refs.inputDate?.inputsRef[3]?.$el">
										<UButton icon="i-lucide-calendar" class="px-0" color="neutral" variant="link" size="sm" />
										<template #content>
											<UCalendar v-model="form.birth_date" class="p-2" />
										</template>
									</UPopover>
								</template>
							</UInputDate>
						</UFormField>

						<UFormField
							name="gender"
							:label="$t('Gender')"
							required
							:error="errors.gender?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<USelect
								v-model="form.gender"
								:loading="ui.isLoadingData"
								class="w-100"
								:items="[
									{ label: $t('Male'), value: 'male' },
									{ label: $t('Female'), value: 'female' }
								]"
							/>
						</UFormField>

						<UFormField
							name="phone_number"
							:label="$t('Phone Number')"
							required
							:error="errors.phone_number?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UInput v-model="form.phone_number" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>

						<UFormField
							name="address"
							:label="$t('Address')"
							required
							:error="errors.address?.[0]"
							:ui="{ labelWrapper: 'w-30 shrink-0' }"
							class="flex items-start gap-4 max-sm:flex-col sm:items-center"
						>
							<UTextarea v-model="form.address" :loading="ui.isLoadingData" class="w-100" />
						</UFormField>
					</UPageCard>

					<div class="flex justify-end gap-3 py-4">
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
			</div>
		</template>
	</DashboardLayout>

	<UModal
		v-model:open="ui.croperModal"
		scrollable
		:title="$t('Crop photo')"
		:description="$t('Adjust the position of your photo')"
	>
		<template #content>
			<cropper
				class="h-128 w-full"
				ref="cropper"
				:src="cropperSrc"
				:stencil-component="$options.components.CircleStencil"
				:stencil-props="{ aspectRatio: 1 }"
				:canvas="{ width: 512, height: 512 }"
			/>
			<UButton
				:label="$t('Apply')"
				icon="i-lucide-check"
				class="rounded-none"
				color="primary"
				size="xl"
				@click="handleCroppedImage"
			/>
		</template>
	</UModal>
</template>

<script>
	/**
	 * ============================================================================
	 * User Profile Management (Options API)
	 * ============================================================================
	 * Comprehensive profile editor with photo cropping and personal data management.
	 *
	 * Features:
	 * - User profile photo upload with circular crop editor
	 * - Identity/personal number validation and tracking
	 * - Full name and birth date entry with calendar picker
	 * - Gender selection dropdown
	 * - Phone number and address entry
	 * - Real-time form validation with error display (422 handling)
	 * - Image cropping with vue-advanced-cropper (circle stencil)
	 * - Multipart form data submission for file uploads
	 * - Automatic data sync after successful save
	 * - Navigation menu with quick links to profile sub-pages
	 * - Responsive layout with touch-friendly controls
	 *
	 * Data Flow:
	 * 1. Component mounts → Fetch current user profile data from API
	 * 2. mapResponseToForm() → Convert API response to form fields (with date conversion)
	 * 3. User selects photo → onFileChange() → Opens cropper modal
	 * 4. Crop circle/position → handleCroppedImage() → Convert to file + preview URL
	 * 5. User submits form → handleSubmit() → prepareFormData() → POST multipart
	 * 6. Success: Show toast + Refresh profile data via fetchData()
	 * 7. Error: Display field-level (422) or generic error messages
	 *
	 * Image Handling:
	 * - File input click triggers via ref binding
	 * - Cropper modal overlays form with 512x512 canvas
	 * - Blob conversion to File object for FormData submission
	 * - URL.createObjectURL for preview display
	 *
	 * Components Used:
	 * - DashboardLayout: Main navigation wrapper
	 * - NavigationMenu: Profile sub-page navigation (Profile, Privacy, Security, Settings)
	 * - Cropper: Vue Advanced Cropper for image editing
	 * - CircleStencil: Circular crop stencil for avatar image
	 * - Calendar: Date picker for birth_date field
	 * ============================================================================
	 */

	// 1. Vue & Core Libraries
	import { shallowRef } from 'vue';
	import { useI18n } from 'vue-i18n';
	import { CalendarDate } from '@internationalized/date';

	// 2. Third-Party Components & Styles
	import { CircleStencil, Cropper } from 'vue-advanced-cropper';
	import 'vue-advanced-cropper/dist/style.css';

	// 3. Networking, Stores, & Local Components
	import instance from '../../../../../resources/js/src/network/instance';
	import { useAuthenticationStore } from '../../../../Authentication/Resources/js/store';
	import DashboardLayout from '../../../../../resources/js/src/components/DashboardLayout.vue';
	import NavigationMenu from '../components/NavigationMenu.vue';

	export default {
		/**
		 * --- COMPONENT REGISTRATION ---
		 */
		components: {
			DashboardLayout,
			NavigationMenu,
			CircleStencil,
			Cropper
		},

		/**
		 * --- COMPOSABLES & INITIALIZATION ---
		 */
		setup() {
			const { locale } = useI18n();
			const toast = useToast();
			const authenticationStore = useAuthenticationStore();

			return {
				locale,
				toast,
				authenticationStore
			};
		},

		/**
		 * --- DATA STATE ---
		 */
		data() {
			return {
				// Route/resource identification
				id: null,

				// Navigation
				links: [
					[
						{ label: 'Profile', icon: 'i-lucide-user', to: '/module/profile', exact: true },
						{ label: 'Privacy', icon: 'i-lucide-shield', to: '/module/profile/privacy' },
						{ label: 'Security', icon: 'i-lucide-lock', to: '/module/profile/security' },
						{ label: 'Settings', icon: 'i-lucide-settings', to: '/module/profile/settings' }
					]
				],

				// UI state flags
				ui: {
					isLoading: false,
					isLoadingData: false,
					croperModal: false
				},

				// Form fields
				form: {
					user_id: null,
					photoFile: null,
					photo_url: null,
					photo: null,
					identity_number: '',
					full_name: '',
					birth_date: null,
					gender: null,
					phone_number: '',
					address: ''
				},

				// Helper/reference data
				cropperSrc: null,

				// Validation errors from backend
				errors: {}
			};
		},

		/**
		 * --- LIFECYCLE HOOKS ---
		 */
		mounted() {
			// Initialize data fetch if user session is active
			if (this.authenticationStore.user?.id) {
				this.form.user_id = this.authenticationStore.user?.id;
				this.fetchData();
			}
		},

		/**
		 * --- METHODS ---
		 */
		methods: {
			// ====== DATA FETCHING & SUBMISSION ======

			/**
			 * Fetches current user profile data from API
			 * Sets isLoadingData flag and clears previous errors
			 */
			async fetchData() {
				this.ui.isLoadingData = true;
				this.errors = {};
				try {
					const { data: response } = await instance.get(`/profile/show/${this.form.user_id}`);
					if (response.data) {
						this.mapResponseToForm(response.data);
					}
				} catch (error) {
					this.handleError(error);
				} finally {
					this.ui.isLoadingData = false;
				}
			},

			/**
			 * Submits form data with multipart file upload
			 * Validates and sends all profile fields including photo
			 * Refreshes profile data on success
			 */
			async handleSubmit() {
				this.ui.isLoading = true;
				this.errors = {};
				try {
					const formData = this.prepareFormData();
					const { data: response } = await instance.post(`/profile/save/${this.form.user_id}`, formData, {
						headers: { 'Content-Type': 'multipart/form-data' }
					});

					this.toast.add({
						title: this.$t('Success'),
						description: response.message,
						icon: 'i-lucide-circle-check',
						color: 'success'
					});

					// Sync local state with server after update
					this.fetchData();
				} catch (error) {
					if (error.response?.status === 422) {
						this.errors = error.response.data.errors;
					} else {
						this.handleError(error);
					}
				} finally {
					this.ui.isLoading = false;
				}
			},

			// ====== IMAGE HANDLING & CROPPING ======

			/**
			 * Triggers file input click via ref binding
			 * Allows user to select image file from device
			 */
			onFileClick() {
				this.$refs.fileRef.click();
			},

			/**
			 * Handles file selection and opens cropper modal
			 * Creates object URL for preview in cropper
			 * Resets input to allow re-selecting same file
			 */
			onFileChange(e) {
				const input = e.target;
				if (!input.files?.length) return;
				this.ui.croperModal = true;
				this.cropperSrc = URL.createObjectURL(input.files[0]);
				input.value = null; // Allow re-selecting the same file
			},

			/**
			 * Converts cropped image canvas to blob and file object
			 * Updates preview URL and stores file for form submission
			 * Closes cropper modal and cleans up object URL
			 */
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

			// ====== DATA MAPPING & FORM HELPERS ======

			/**
			 * Maps API response object to form data structure
			 * Converts date strings to CalendarDate objects for date picker
			 * Handles null/undefined values gracefully
			 *
			 * @param {Object} profile - Profile data from API response
			 */
			mapResponseToForm(profile) {
				this.id = profile.id;
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
			 * Prepares form data for multipart submission
			 * Includes all form fields and appends file only if user selected new photo
			 * Returns FormData object compatible with axios multipart submission
			 *
			 * @returns {FormData} Prepared multipart form data for API submission
			 */
			prepareFormData() {
				const formData = new FormData();

				// Populate basic fields
				formData.append('user_id', this.form.user_id);
				formData.append('identity_number', this.form.identity_number);
				formData.append('full_name', this.form.full_name);
				formData.append('birth_date', this.form.birth_date || '');
				formData.append('gender', this.form.gender || '');
				formData.append('phone_number', this.form.phone_number);
				formData.append('address', this.form.address);

				// Add file if user changed the photo
				if (this.form.photoFile) {
					formData.append('photo', this.form.photoFile);
				}
				return formData;
			},

			// ====== ERROR HANDLING ======

			/**
			 * Centralized error handling for API requests
			 * Displays error message via toast notification
			 * Logs error to console for debugging
			 *
			 * @param {Error} error - Error object from API request
			 */
			handleError(error) {
				this.toast.add({
					title: 'Error',
					description: error.response?.data?.message || this.$t('Something went wrong'),
					icon: 'i-lucide-ban',
					color: 'error'
				});
			}
		}
	};
</script>
