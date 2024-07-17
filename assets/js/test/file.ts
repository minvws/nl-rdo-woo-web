interface TestFileProperties {
  lastModified?: Date;
  name?: string;
  type?: string;
}

export const createTestFile = (properties: TestFileProperties = {}) => {
  const { name = 'file.txt', lastModified = new Date(2020, 1, 2), type = 'text/plain' } = properties;
  return new File([name], name, { lastModified: lastModified.getTime(), type });
};
