const base = '/masteriyo/v1/';
export const urls = {
	migrationLMSs: base + 'migrations/lms',
	migrations: base + 'migrations',
	migrationStart: base + 'migrations/start',
	migrationActive: base + 'migrations/active',
	migrationDeactivateSource: base + 'migrations/deactivate-source',
	migrationStatus: (id: string) => `${base}migrations/${id}`,
	migrationCancel: (id: string) => `${base}migrations/${id}`,
};
