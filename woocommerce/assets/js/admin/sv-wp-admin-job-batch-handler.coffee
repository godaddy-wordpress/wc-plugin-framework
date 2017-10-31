###*
# WordPress Batch Job Handler
#
# @since 4.8.0
###

jQuery( document ).ready ( $ ) ->
	'use strict'


	# Handles batch processing job items.
	#
	# @since 4.8.0
	class window.SV_WP_Job_Batch_Handler


		# Constructs the class.
		#
		# @since 4.8.0
		#
		# @params [Object] args with properties:
		#     id:    job handler ID, used for naming actions and events
		#     nonce: nonce for AJAX requests
		constructor: ( args ) ->

			@id            = args.id
			@process_nonce = args.process_nonce
			@cancel_nonce  = args.cancel_nonce
			@cancelled     = false


		# Processes a given job ID in batches.
		#
		# @since 4.8.0
		#
		# @param [String] an existing job ID
		# @returns [Promise]
		process_job: ( job_id ) => new Promise ( resolve, reject ) =>

			# halt batch processing if a job is cancelled by user action
			return this.cancel_job( job_id ) if @cancelled is job_id

			data =
				action:   "#{@id}_process_batch"
				security: @process_nonce
				job_id:   job_id

			$.post( ajaxurl, data )

				.done ( response ) =>

					# trigger an error if an error is returned or the job data is missing
					return reject response unless response.success and response.data?

					# we're done if the job is anything but still processing
					return resolve response unless response.data.status is 'processing'

					# broadcast the job progress
					$( document ).trigger "#{@id}_batch_progress_#{response.data.id}",
						percentage: response.data.percentage
						progress:   response.data.progress
						total:      response.data.total

					# continue processing until finished
					return resolve this.process_job( response.data.id )

				.fail ( jqXHR, textStatus, error ) ->

					reject error # TODO: anything more we can do here?


		# Cancels a given job.
		#
		# @since 4.8.0
		#
		# @param [String] an existing job ID
		# @returns [Promise]
		cancel_job: ( job_id ) -> new Promise ( resolve, reject ) =>

			@cancelled = false

			data =
				action:   "#{@id}_cancel_job"
				security: @cancel_nonce
				job_id:   job_id

			$.post( ajaxurl, data )

				.done ( response ) ->

					return reject response unless response.success

					return resolve response

				.fail ( jqXHR, textStatus, error ) ->

					reject error
