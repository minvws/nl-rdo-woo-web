interface TestFileProperties {
  lastModified?: Date;
  name?: string;
  size?: number;
  type?: string;
}

export const createTestFile = (properties: TestFileProperties = {}) => {
  const {
    name = 'file.txt',
    lastModified = new Date(2020, 1, 2),
    size,
    type = 'text/plain',
  } = properties;
  return new File(['x'.repeat(size ?? name.length)], name, {
    lastModified: lastModified.getTime(),
    type,
  });
};
